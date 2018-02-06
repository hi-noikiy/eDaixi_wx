set :application, 'weixin'
set :repo_url, 'git@github.com:rongchang/weixin.git'
ask :branch, ENV['branch']||'master'

set :scm, :git
set :format, :pretty
set :pty, true
set :log_level, :info

set :keep_releases, 10

namespace :deploy do
  task :assets do
    on roles(:pro) do
      within release_path do
        ts = "#{Time.now.to_i}"
        execute %[mkdir -p "#{deploy_to}/current/#{ts}"]
        execute %[ln -s #{deploy_to}/current/source  #{deploy_to}/current/#{ts}]
        execute %[ln -s #{deploy_to}/current/resource  #{deploy_to}/current/#{ts}]
        execute %[ln -s #{deploy_to}/current/themes  #{deploy_to}/current/#{ts}]
        execute %[ln -s #{deploy_to}/current/framework  #{deploy_to}/current/#{ts}]
        execute %[cp #{deploy_to}/shared/data/config.php  #{deploy_to}/current/data/config.php]
        execute %[sed -i 's/1415722537/#{ts}/g' #{deploy_to}/current/data/config.php]
        execute %[rm -rf #{deploy_to}/current/data/logs && ln -s /log/app  #{deploy_to}/current/data/logs]
      end
    end
    on roles(:huidu) do
      within release_path do
        ts = "#{Time.now.to_i}"
        execute %[mkdir -p "#{deploy_to}/current/#{ts}"]
        execute %[ln -s #{deploy_to}/current/source  #{deploy_to}/current/#{ts}]
        execute %[ln -s #{deploy_to}/current/resource  #{deploy_to}/current/#{ts}]
        execute %[ln -s #{deploy_to}/current/themes  #{deploy_to}/current/#{ts}]
        execute %[ln -s #{deploy_to}/current/framework  #{deploy_to}/current/#{ts}]
        execute %[cp #{deploy_to}/shared/data/config.php  #{deploy_to}/current/data/config.php]
        execute %[sed -i 's/1415722537/#{ts}/g' #{deploy_to}/current/data/config.php]
        execute %[rm -rf #{deploy_to}/current/data/logs && ln -s /log/app/weixin  #{deploy_to}/current/data/logs]
      end
    end
    on roles(:test) do
      within release_path do
        ts = "#{Time.now.to_i}"
        execute %[mkdir -p "#{deploy_to}/current/#{ts}"]
        execute %[ln -s #{deploy_to}/current/source  #{deploy_to}/current/#{ts}]
        execute %[ln -s #{deploy_to}/current/resource  #{deploy_to}/current/#{ts}]
        execute %[ln -s #{deploy_to}/current/themes  #{deploy_to}/current/#{ts}]
        execute %[ln -s #{deploy_to}/current/framework  #{deploy_to}/current/#{ts}]
        execute %[cp #{deploy_to}/shared/data/config.php  #{deploy_to}/current/data/config.php]
        execute %[rm -rf #{deploy_to}/shared/data/tpl/*]
        execute %[sed -i 's/1415722537/#{ts}/g' #{deploy_to}/current/data/config.php]
       end
     end
  end
  after  :finishing, 'deploy:assets'
end


