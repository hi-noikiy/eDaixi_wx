if not ENV['to'].nil? and ENV['to'].include?('test')
    server ENV['to'], roles: %w(web app test)
    set :default_env, {
        'SERVERCODE' => ENV['to']
    }
else
    puts " Error deploy , usag: to=test[0-9] cap staging deploy"
    puts
    exit
end


set :stage, 'production'
set :deploy_to, '/data/www/app/weixin'

set :linked_files, fetch(:linked_files, []).push('source/alipay/config.php')
set :linked_dirs, fetch(:linked_dirs, []).push('data/logs','data/tpl','resource/attachment')

set :ssh_options, {
      user: 'ubuntu',
      port: '22',
      forward_agent: true,
      auth_methods: ["publickey"],
      keys: ['~/.ssh/TnWqFPNVUkkeQNzUNb.pem']
    }

set :jianliao_url, 'https://jianliao.com/v2/services/webhook/a0617fb17751e5c28916468e2812a58f8e268463'
set :jianliao_application, ENV['to']+'_weixin'
