<div class='span-24 last'>
    <form action="inbox.php" method="post" id="inbox-form" class="vertical">
        <table>
            <caption>
                <?php echo $num_msgs ?> message<?php if ($num_msgs > 1) echo 's' ?> remaining to be archived
            </caption>
            <tr>
                <td>Actions:</td>
                <td>
                    <input type="submit" name="save" value="Archive + Delete" />
                    <input type="submit" name="save" value="Archive Only" />
                    <input type="submit" name="delete" value="Delete Only" />
                </td>
            </tr>
            <tr>
                <th><label for="date_and_time">Date:</label></th>
                <td>
                    <input type="text" name="date_and_time" id="date_and_time"
                           value="<?php echo htmlentities($editform_defaults['date_and_time']) ?>" />
                </td>
            </tr>
            <tr>
                <th><label for="from_id">From:</label></th>
                <td>
                    <select name="from_id" id="from_id">
                        <?php foreach ($people as $pid=>$pname): ?>
                        <option value="<?php echo $pid ?>" <?php if ($pid==$editform_defaults['from_id']) echo 'selected' ?>>
                            <?php echo $pname ?>
                        </option>
                        <?php endforeach ?>
                    </select>
                    <a href='people.php?person_text="<?php echo $headers['FROM'] ?>'>
                        <code><?php echo htmlentities($headers['FROM']) ?></code>
                    </a>
                    (<?php echo $editform_defaults['from_id'] ?>)
                </td>
            </tr>
            <tr>
                <th><label for="to_id">To:</label></th>
                <td>
                    <select name="to_id" id="to_id">
                        <?php foreach ($people as $pid=>$pname): ?>
                        <option value="<?php echo $pid ?>" <?php if ($pid==$editform_defaults['to_id']) echo 'selected' ?>>
                            <?php echo $pname ?>
                        </option>
                        <?php endforeach ?>
                    </select>
                    <a href='people.php?person_text="<?php echo $headers['TO'] ?>'>
                        <code><?php echo htmlentities($headers['TO']) ?></code>
                    </a>
                    (<?php echo $editform_defaults['to_id'] ?>)
                </td>
            </tr>
            <tr>
                <th><label for="subject">Subject:</label></th>
                <td>
                    <input type="text" name="subject" id="subject"
                           value="<?php echo htmlentities($editform_defaults['subject']) ?>" />
                </td>
            </tr>
            <tr>
                <th><label for="message_body">Message:</label></th>
                <td><textarea name="message_body" rows="24" cols="80"><?php echo htmlentities($editform_defaults['message_body']) ?></textarea></td>
            </tr>
        </table>
    </form>
</div>
