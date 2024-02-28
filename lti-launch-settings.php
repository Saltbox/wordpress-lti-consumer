<?php
$roles = array(
    'Student',
    'Faculty',
    'Member',
    'Learner',
    'Instructor',
    'Mentor',
    'Staff',
    'Alumni',
    'ProspectiveStudent',
    'Guest',
    'Other',
    'Administrator',
    'Observer',
    'None');
?>
<table class="form-table">
    <tbody>
        <tr>
            <th><label for="lti_content_field_"><?php echo _e("OAuth Consumer Key", 'lti-consumer'); ?></label></th>
            <td><input type="text" id="lti_content_field_consumer_key" name="lti_content_field_consumer_key" value="<?php echo esc_attr($consumer_key); ?>" size="25" /></td>
        </tr>

        <tr>
            <th><label for="lti_content_field_"><?php echo _e("OAuth Secret Key", 'lti-consumer'); ?></label></th>
            <td><input type="password" id="lti_content_field_secret_key" name="lti_content_field_secret_key" value="<?php echo esc_attr($secret_key); ?>" size="25" /></td>
        </tr>

        <tr class="display_tr">
            <th><label for="lti_content_field_display_newwindow"><?php echo _e("Display Style", 'lti-consumer'); ?></label></th>
            <td>
                <label>Open in a new browser window <input type="radio" <?php checked($display, 'newwindow'); ?> id="lti_content_field_display_newwindow" name="lti_content_field_display" value="newwindow" /></label><br>
                <label>Inline in an iframe <input type="radio" <?php checked($display, 'iframe'); ?> id="lti_content_field_display_iframe" name="lti_content_field_display" value="iframe" /></label><br>
                <label>Open in the current browser window <input type="radio" <?php checked($display, 'self'); ?> id="lti_content_field_display_self" name="lti_content_field_display" value="self" /></label>
            </td>
        </tr>

        <tr class="action_button_tr">
            <th><label for="lti_content_field_action_button"><?php _e("Launch trigger control", 'lti-consumer'); ?></label></th>
            <td>
                <label>Button <input type="radio" <?php checked($action, 'button'); ?> id="lti_content_field_action_button" name="lti_content_field_action" value="button" /></label><br>
                <label>Link <input type="radio" <?php checked($action, 'link'); ?> id="lti_content_field_action_link" name="lti_content_field_action" value="link"  /></label>
            </td>
        </tr>

        <tr>
            <th><label for="lti_content_field_launch_url"><?php echo _e("Launch URL", 'lti-consumer'); ?></label></th>
            <td><input type="url" id="lti_content_field_launch_url" name="lti_content_field_launch_url" value="<?php echo esc_attr($launch_url); ?>" size="35" /></td>
        </tr>

        <tr>
            <th><label for="lti_content_field_configuration_url"><?php echo _e("Configuration XML URL", 'lti-consumer'); ?></label></th>
            <td><input type="url" id="lti_content_field_configuration_url" name="lti_content_field_configuration_url" value="<?php echo esc_attr($configuration_url) ?>" size="35" /></td>
        </tr>

        <tr>
            <th><label for="lti_content_field_return_url"><?php echo _e("Return URL after completion", 'lti-consumer'); ?></label></th>
            <td><input type="url" id="lti_content_field_return_url" name="lti_content_field_return_url" value="<?php echo esc_attr($return_url); ?>" size="35" /></td>
        </tr>

        <tr>
            <th><label for="lti_content_field_version_1_1"><?php _e("LTI version", 'lti-consumer'); ?></label></th>
            <td>
                <label>1.1 <input type="radio" <?php checked($version, 'LTI-1p1'); ?> id="lti_content_field_version_1_1" name="lti_content_field_version" value="LTI-1p1" /></label><br>
                <label>1.0 <input type="radio" <?php checked($version, 'LTI-1p0'); ?> id="lti_content_field_version_1_0" name="lti_content_field_version" value="LTI-1p0"  /></label>
            </td>
        </tr>

        <tr>
            <th><label for="lti_content_field_instructor_user"><?php echo _e("Use this instructor user instead of the logged in user", 'lti-consumer'); ?></label></th>
            <td>
                <select id="lti_content_field_instructor_user" name="lti_content_field_instructor_user">
                    <option value="-1"></option>
                    <?php
                    foreach ($users as $user) {
                        echo '<option  value="' . $user->id . '" ' . ($instructor_user == $user->id ? 'selected' : '') . ' >' . esc_html($user->display_name) . ' [' . esc_html($user->user_email) . ']</option >';
                    }
                    ?>
                </select >

            </td>
        </tr>

        <tr>
            <th><label for="lti_content_field_role"><?php echo _e("Institution Role to use", 'lti-consumer'); ?></label></th>
            <td>
                <select id="lti_content_field_role" name="lti_content_field_role">
                    <option value="-1"></option>
                    <?php
                    foreach ($roles as $role) {
                        echo '<option  value="' . $role . '" ' . ($institution_role == $role ? 'selected' : '') . ' >' . $role . '</option >';
                    }
                    ?>
                </select >

            </td>
        </tr>

        <tr>
            <th><label for="lti_content_field_other_parameters"><?php echo _e('Add a JSON object of other parameters and values sample: {"key":"value","key2":"value2"} ', 'lti-consumer'); ?></label></th>
            <td><textarea id="lti_content_field_other_parameters" name="lti_content_field_other_parameters" rows="5" cols="40"><?php echo esc_attr($other_parameters); ?></textarea></td>
        </tr>
    </tbody>
</table>