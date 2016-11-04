# Wp Instagram Wall

Wp Instagram Wall is a Wordpress plugin which allow you to easily add a wall of your Instagram pics on your website. The module use cache to avoid too many connections to Instagram.

### Installation
- Download the project
- Upload the directory "wp-instagram-wall" in your module's folder
- On admin plugin's page, enable the module

### Configuration
- A configuration page is available for the module, follow the link "Instagram Wall" in the menu
- You'll need o create an Instagram App here : https://www.instagram.com/developer/
- Your app can be in Sandbox Mode, the module will still work (because you only display your account's pics)
- Make sure your redirect uri is correct in your app (the correct url is given by the module, on configuration page)
- Fill the form with your app information (Client id and Secret id)
- Find your Instagram user id with this tool : https://www.otzberg.net/iguserid/
- Click on the button "Get my token", you will be redirected to an Instagram page, to allow your app to access your pics
- The field "token" will be filled automatically
- Save

### Templating
- 3 templates are available :
    - default.php : pics are displayed in squares, with backgroud-cover property
    - bootstrap.php : pics are displayed with Twitter Bootstrap div
    - images.php : pics are displayed in img tags

### Display wall in a template
Use this php code anywhere in your template :
```sh
<?php
echo Wp_Instagram_Wall_Plugin::getInstance()->generateWall();
?>
```

### Common errors
- app id, secret id or user id are incorrect
- redirect url isn't correct on you app
- your wp-content/uploads directory isn't writtable

### Todo
- add the possibility to create new templates
- add a button to clear the cache
- add an option to choose the cache duration

### Module in action
- http://www.voyatopia.com/