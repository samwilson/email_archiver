<form action="index.php" method="post" id="reply-form">
    <table>
        <caption>Compose</caption>
        <tr>
            <th><label for="to">To:</label></th>
            <td>
				<?php echo $to['name'].' &lt;'.$to['email_address'].'&gt;' ?>
				<input type="hidden" name="to" id="to" value="<?php echo $to['id'] ?>" />
            
            </td>
        </tr>
        <tr>
            <th><label for="subject">Subject:</label></th>
            <td><input type="text" name="subject" id="subject" value="<?php echo $subject ?>" size="80"/></td>
        </tr>
        <tr>
            <td></td>
            <td><textarea name="message_body" rows="24" cols="80"></textarea></td>
        </tr>
        <tr>
            <td></td>
            <td>
                <input type="submit" name="send" value="Send" />
                <input type="hidden" name="with" value="<?php echo $with ?>" />
                <input type="hidden" name="year" value="<?php echo $year ?>" />
                <input type="hidden" name="last_date" value="<?php echo $last_date ?>" />
                <input type="hidden" name="year" value="<?php echo $year ?>" />
                <?php if ($last_from_id != MAIN_USER_ID): ?>
                    <input type="hidden" name="last_body" value="<?php echo htmlentities($last_body) ?>" />
                <?php endif ?>
            </td>
        </tr>
    </table>
</form>
