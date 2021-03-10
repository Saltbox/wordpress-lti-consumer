An LTI-compatible launching plugin for Wordpress.

General Wordpress plugin installation instructions can be found here: http://codex.wordpress.org/Managing_Plugins#Automatic_Plugin_Installation

### Using shortcodes:

After installing the plugin, add content launching with the [lti-launch]
shortcode.


Some examples:

    [lti-launch consumer_key=yourconsumerkey secret_key=yoursecretkey display=iframe configuration_url=http://launcher.saltbox.com/lms/configuration resource_link_id=testcourseplacement1]
    
    [lti-launch consumer_key=yourconsumerkey secret_key=yoursecretkey display=newwindow action=link configuration_url=http://launcher.saltbox.com/lms/configuration resource_link_id=testcourseplacement1]
    
    [lti-launch consumer_key=yourconsumerkey secret_key=yoursecretkey display=self action=button launch_url=http://launcher.saltbox.com/launch resource_link_id=testcourseplacement1]


Options:

- display

  - newwindow: launches into a new window

  - self: launches into the same window, replacing the current content

  - iframe: launches into an iframe embedded in the content

- action

  - button: shows a button to the user, which they click on to launch

  - link: shows a link to the user, which they click on to launch

- configuration_url: the URL to the launch XML configuration

- launch_url: The launch URL of the LTI-compatible tool


Caution!  Since shortcodes are visible to content viewers if their plugin is
disabled, OAuth secret keys will become visible if this plugin is disabled.

### Using TLI Content - Launch Settings:

After installing this plugin, you will notice a new "LTI content" menu item in the WP Admin dashboard. Here you will be able to create a custom post, witch in the end generates a shortcode that can be used on any page.

TLI Launch setting example::
![Screenshot](/LTI-Launch-settings.jpg)

Copyright (c) 2021 Saltbox Services.
Licensed under the GPLv3. See the LICENSE.md file for details.

