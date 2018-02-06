#!/bin/bash

run_pre() {
    TESTDB='testdb.bce.edaixi.cn' # 百度云测试数据机
    # 适配测试环境
    if [ ${MEMCACHE_PORT} ]; then
        export MEMCACHE_EDX=${MEMCACHE_PORT#tcp://}
        export MEMCACHE_EDX_ADDR=${MEMCACHE_EDX%:*}
        export MEMCACHE_EDX_PORT=${MEMCACHE_EDX##*:}
    else
        export MEMCACHE_EDX="${TESTDB}:112${APP_NAME:=11}"
        export MEMCACHE_EDX_ADDR='${TESTDB}'
        export MEMCACHE_EDX_PORT="112${APP_NAME:=11}"
    fi
    if [ ${MYSQL_PORT} ]; then
        export MYSQL_EDX=${MYSQL_PORT#tcp://}
        export MYSQL_EDX_ADDR=${MYSQL_EDX%:*}
        export MYSQL_EDX_PORT=${MYSQL_EDX##*:}
    else
        export MYSQL_EDX="${TESTDB}:33${APP_NAME:=06}"
        export MYSQL_EDX_ADDR='${TESTDB}'
        export MYSQL_EDX_PORT="33${APP_NAME:=06}"
    fi
    if [ ${REDIS_PORT} ]; then
        export REDIS_EDX=${REDIS_PORT#tcp://}
        export REDIS_EDX_ADDR=${REDIS_EDX%:*}
        export REDIS_EDX_PORT=${REDIS_EDX##*:}
    else
        export REDIS_EDX="${TESTDB}:63${APP_NAME:=79}"
        export REDIS_EDX_ADDR='${TESTDB}'
        export REDIS_EDX_PORT="63${APP_NAME:=79}"
    fi
    if [ ${REDIS2_PORT} ]; then
        export REDIS2_EDX=${REDIS2_PORT#tcp://}
        export REDIS2_EDX_ADDR=${REDIS2_EDX%:*}
        export REDIS2_EDX_PORT=${REDIS2_EDX##*:}
    else
        export REDIS2_EDX="${TESTDB}:63${APP_NAME:=80}"
        export REDIS2_EDX_ADDR='${TESTDB}'
        export REDIS2_EDX_PORT="63${APP_NAME:=80}"
    fi
    if [ ${ROUTER_PORT} ]; then
        export ROUTER_EDX=${ROUTER_PORT#tcp://}
        export ROUTER_EDX_API=${ROUTER_PORT_12121_TCP#tcp://}
    fi
    # 适配测试环境的sso，生成唯一sso client uid
    if [ ${PORT_80_HTTP} ]; then
        SERVICE_TMP=${PORT_80_HTTP#http://}
        SERVICE_URL=${PORT_80_HTTP}
    elif [ ${PORT_80_TCP} ]; then
        SERVICE_TMP=${PORT_80_TCP#tcp://}
        SERVICE_URL="http://${SERVICE_TMP}"
    fi
    if [[ ${SERVICE_TMP%%\.*} = ${SERVICE_NAME} ]]; then
        echo ${SERVICE_NAME^^}
        eval "export ${SERVICE_NAME^^}_PORT=${SERVICE_URL}"
    fi
    export SERVICE_TMP=${SERVICE_TMP%-*}
    export SSO_ID_PREFIX=${SERVICE_TMP/-/.}
    export SSO_ID="${SSO_ID_PREFIX}.docker"
    # 如果是需要多容器同步的时间戳, 使用这个变量, 等号后面的默认值可以替换
    export TS=$(date +"%s" -d "${__CREATE_TIME__}")

    # mkdir -p /var/log/app /app/log /app/logs/app /app/logs/nginx /data/log /data/logs /log/app
    mkdir -p /app/log/app /app/log/nginx /data
    ln -s /app/log /log
    ln -s /app/log /app/logs
    ln -s /app/log /data/log
    ln -s /app/log /data/logs
    ln -s /app/log/app /var/log/app
    chmod -R 777 /app

    # 拉取配置文件
    git clone git@gitlab.edaixi.com:niexz/docker-configure-online.git
    # 添加线上host绑定
    cat docker-configure-online/${RUN_ENV}/hosts >> /etc/hosts
}


# TEST_NAME="test07"
get_pillar_conf() {
    pyscript="import yaml,sys\\nd=yaml.safe_load(sys.stdin).get('${APP_NAME}')\\nfor k,v in d.items():\\n    c='WECHAT_{0}={1}'.format(k.upper(), v)\\n    print(c)"
    d=$(python -c "exec(\"${pyscript}\")" < docker-configure-online/${RUN_ENV}/pillar.yml)
    if [ $? = 0 ]; then
        for i in ${d}; do
             eval "export ${i}"
        done
    fi
}

run_in_test() {
    run_pre

    # 生成nginx的logid
    export SVC_ID="${SSO_ID_PREFIX//./0}0t"

    # 获得 pillar.yml中的配置
    get_pillar_conf

    cd docker
    ln -s /app/docker-configure-online/${RUN_ENV}/weixin ./tpl
    # 此工具需要保持目录格式相同
    ./gen_configuration.py tpl/config/ /app/
    ./gen_configuration.py tpl/app.conf nginx/app.conf
    # ./gen_configuration.py nginx/app.tpl nginx/app.conf
    cd -

    run_post
}


run_in_production() {
    run_pre
    # 此功能需要保持目录格式相同
    rsync -av docker-configure-online/${RUN_ENV}/weixin/config/ /app/
    cat docker-configure-online/${RUN_ENV}/weixin/app.conf > /app/docker/nginx/app.conf
    export SVC_ID="${SSO_ID_PREFIX}"   # 生成nginx的logid
    crontab /app/docker/crontab # load crontab
    run_post
}


run_post() {

    mkdir -p /app/${TS}
    ln -s /app/source /app/${TS}
    ln -s /app/resource /app/${TS}
    ln -s /app/themes /app/${TS}
    ln -s /app/framework  /app/${TS}
    #替换时间戳
    sed -i 's/1415722537/${TS}/g' /app/data/config.php

    # 设置log tag，输出到elk
    sed -i "/access_log/s/|SVC_ID|/${SVC_ID}/" /app/docker/nginx/app.conf
    sed -i "/FileTag/s/|SVC_ID|/${SVC_ID}/" /app/docker/rsyslog.conf
    
    find /app -type d  -exec chmod 777 {} \;
    find /app -type f ! -path "/app/docker*" -exec chmod 666 {} \;

    rm -rf docker-configure-online
}


case ${RUN_ENV:=production} in
    production)
        echo "production"
        run_in_production
        ;;
    test)
        echo "test"
        run_in_test
        ;;
    *)
        echo "default"
        run_in_production
        ;;
esac


exec "$@"

