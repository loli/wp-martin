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


## contribution 2

### task
Upon submitting, an email is send to a) the company and b) the customer. The uploaded file is attached to both by default. The company would prefer that at least for their e-mail the attachment is replaced by a link to the file.

### solution
Wrote a small plugin (attachment-avoider-contact-form-7.php) that registers a filter into *wpcf7_mail_components*.

Uses:
- *wpcf7_mail_components* hook: http://hookr.io/filters/wpcf7_mail_components/
- *add_filter* from wordpress: https://developer.wordpress.org/reference/functions/add_filter/

### usage
Using *contanct form 7* and *save contact form 7*, this plugin replaces all e-mail attachements with a hyperlink. This behaviour is triggered by adding an `[attachments-to-links]` tag to the e-mail body. Note: Because of a bug in *save contact form 7*, only one attachement can be converted to a valid link.

### examples
**Message Body** field
```
From: [your-name] <[your-email]>
Subject: [your-subject]

Message Body:
[your-message]

Attachments:
[attachments-to-links]
```
You are still required to add the name of your upload to the **File Attachments** field, e.g.,
```
[your-file]
```

## contribution 3

### task
The *save-contact-form-7* plugin contains a bug that prohibits the upload of more than one file for a single form.

### solution
Wrote a patch for *save-contact-form-7*. The patched version 2.0.1 (base don version 2.0.0) can be found in this repository.

### usage
Simply replace the folder `[wordpress]/wp-content/plugins/save-contact-form-7/` with the folder of the same name from this repository. After the switch, the information about the *save-contact-form-7* in the admin/plugins panel should read *Version 2.0.1*.

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
