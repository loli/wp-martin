# wp-martin
Martin's famous WP form changes


## contribution 1
### task
Upon submitting the contact form, all fields reset to their default value automatically. How to achieve that selected fields maintain their value after submission?

### solution
Look for file `[wordpress]/wp-content/plugins/contact-form-7/includes/js/scripts.js` and replace
```js
  if ( 'mail_sent' == data.status ) {
	  $form.each( function() {
		  this.reset();
	  } );
  }
```
with
```js
  if ( 'mail_sent' == data.status ) {
	  $form.each( function() {
	    var $elements = this.elements;
	    for (var index = 0; index < $elements.length; ++index) {
	      if (!$elements[index].classList.contains('permanent-entry')) {
	        $elements[index].value = $elements[index].defaultValue;
	      }
	    }
	  } );
  }
```
## usage
To make a field value permanent after submission, simply add the class 'permanent-entry' to it. E.g.:
```
  [text* your-name class:permanent-entry]
```
This will work with all types of fields.


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
