An LTI-compatible launching plugin for Wordpress.


General Wordpress plugin installation instructions can be found here: http://codex.wordpress.org/Managing_Plugins#Automatic_Plugin_Installation


After installing the plugin, add content launching with the [lti-launch]
shortcode.


Some examples::

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



Copyright (c) 2014 Saltbox Services.
Licensed under the GPLv3. See the LICENSE.md file for details.

