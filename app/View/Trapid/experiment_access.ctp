<div class="page-header">
    <h1 class="text-primary">Share experiment</h1>
</div>
<section class="page-section-sm">
    <?php if ($is_owner) {
        echo $this->Form->create(false, [
            'id' => 'exp_access_form',
            'url' => ['controller' => 'trapid', 'action' => 'experiment_access', $exp_id],
            'type' => 'post'
        ]);
        echo "<input name='exp_access_change' id='exp_access_change' value='share' type='hidden'>";
    } ?>
    <h3>Current experiment access</h3>
    <table class="table table-striped table-hover table-condensed">
        <thead>
            <tr>
                <th>Group</th>
                <th>Email address</th>
                <th>Revoke access</th>
            </tr>
        </thead>
        <tbody>
            <?php
            foreach ($shared_users['owner'] as $k => $v) {
                echo '<tr>';
                echo "<td><span class='text-info'><strong>Owner</strong></span></td>";
                echo "<td><a href='mailto:" . $v . "'>" . $v . '</a></td>';
                echo "<td class='text-muted'>-</td>";
                echo "</tr>\n";
            }

            asort($shared_users['shared']);
            foreach ($shared_users['shared'] as $k => $v) {
                echo '<tr>';
                echo "<td><span class='text-success'><strong>Shared</strong></span></td>";
                echo "<td><a href='mailto:" . $v . "'>" . $v . '</a></td>';
                if ($is_owner) {
                    echo "<td><a href='#' onclick='revokeExperimentAccess(\"" .
                        $v .
                        "\")' title='Revoke access to this user'>Revoke access</a></td>";
                } else {
                    echo "<td class='text-muted'>-</td>";
                }
                echo "</tr>\n";
            }
            ?>
        </tbody>
    </table>
</section>

<section class="page-section-sm">
    <?php if ($is_owner) {
        echo "<h3>Change experiment access</h3>\n";
        echo "<p class='text-justify'>Please provide the email addresses of TRAPID users with whom you want to share this experiment (one email address per line).</p>\n";
        echo "<div class='form-group'>\n";
        echo "<label for='new_share'><strong>Email address(es)</strong></label>\n";
        echo "<textarea class='form-control mw-800' name='new_share' rows='3' cols='80' placeholder='user1@example.org\nuser2@example.org\n...' required></textarea>\n";
        echo "</div>\n";
        echo "<button type='submit' class='btn btn-default btn-sm'><span class='glyphicon glyphicon-share-alt'></span> Share experiment</button>\n";
        echo $this->Form->end();
        echo "</div>\n";
    } else {
        echo "<p class='text-justify'><strong>Note:</strong> only the owner of an experiment is allowed change the access settings.</p>\n";
    } ?>
</section>

<script type="text/javascript">
    function revokeExperimentAccess(userEmail) {
        let expAccessForm = document.getElementById("exp_access_form");
        let expAccessChange = document.getElementById("exp_access_change");
        if (typeof(userEmail) === "string" && confirm("Are you sure you want to stop sharing this experiment with " + userEmail + "?")) {
            expAccessChange.value = "revoke";
            // Create input element and submit form
            const input = document.createElement("input");
            input.setAttribute("name", "revoke_email");
            input.setAttribute("value", userEmail);
            input.setAttribute("type", "hidden");
            expAccessForm.appendChild(input);
            expAccessForm.submit();
        }
    }
</script>
