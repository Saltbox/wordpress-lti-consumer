<?php

namespace Saltbox;
?>
<h1>LTI Content Options</h1>

<form method="POST">

    <table class="form-table" role="presentation">
        <tbody>
            <tr>
                <th scope="row"><label for="lti_instructor_user_role">Instructor user role's</label></th>
                <td>
                    <select id="lti_instructor_user_role" name="lti_instructor_user_role[]" multiple  style="height: 200px;">
                        <option value="-1"></option>
                        <?php
                        foreach ($editable_roles as $role => $details) {
                            echo '<option value="' . esc_attr($role) . '" ' . (in_array(esc_attr($role), $instructor_role) ? 'selected' : '') . ' >' . translate_user_role($details['name']) . '</option >';
                        }
                        ?>
                    </select>
                </td>
            </tr>            
        </tbody>
    </table>

    <?php wp_nonce_field('sb_lti_options_page_action', '_wpnonce_sb_lti_options_page_action'); ?>
    <p class="submit">
        <input type="submit" value="Save" class="button button-primary button-large">
    </p>
</form>
