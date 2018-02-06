server 'huidu1', roles: %w(web app huidu)

set :stage, :production
set :branch, 'master'
set :deploy_to, "/data/www/app/weixin"

set :linked_dirs, fetch(:linked_dirs, []).push('data/tpl','resource/attachment')
set :linked_files, fetch(:linked_files, []).push('source/alipay/config.php')

set :ssh_options, {
      user: 'ubuntu',
      port: '22',
      forward_agent: true,
      auth_methods: ["publickey"],
      keys: ['/capistrano/key/id_rsa']
    }

set :jianliao_url,'https://jianliao.com/v2/services/webhook/d797f4bacfb3c8a47490cbacc777ae974d41f210'
set :jianliao_application, 'huidu-weixin'
