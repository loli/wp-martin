# wp-martin
Martin's famous WP form changes

## local server configuration
**location** : http://localhost/wp-martin/

### Apache2, mysql, etc.
```
sudo apt-get install apache2
sudo apt-get install mysql-server mysql-client
sudo apt-get install php phpmyadmin
```

In `/etc/apache2/apache2.conf` add
```
<Directory /home/<user>/Workspace/www/>
  Options Indexes FollowSymLinks
  AllowOverride None
  Require all granted
</Directory>
```

### Wordpress plugins
**location** : <wordpress>/wp-content/plugins/

- Contact Form 7 (v4.8)
- Save Contact Form 7 (2.0)

### PHP mail() command
```
sudo aptitude install sendmail
sudo sendmailconfig
```
and answer all with `Y`
```
sudo gedit /etc/hosts
```
add line `127.0.0.1 localhost localhost.localdomain decrepito` to top (decrepito determined via `hostname` command)
```
sudo service apache2 restart
```
(source: https://lukepeters.me/blog/getting-the-php-mail-function-to-work-on-ubuntu)

Now test it with
```
echo "test message" | sendmail -v user@mail.com
```
which might cause an error, as your localhost is not registered at any DNS server. You can alternatively use
```
echo "test message" | sendmail -v user@localhost.localdomain
```
which will send the mail to your local computer. You can then read it with the `mail` command. Hence, user `user@localhost.localdomain` as target mail address if others fail.
