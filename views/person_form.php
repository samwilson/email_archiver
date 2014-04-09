<div class='span-24 last noprint'>
    <form action="people.php" method="post" id="reply-form">
        <table>
            <caption>Person <?php if (!empty($person['id'])) echo '#' . $person['id'] ?></caption>
            <tr>
                <th><label for="name">Name:</label></th>
                <td>
                    <input type="text" name="name" id="name" value="<?php echo htmlentities($person['name']) ?>" />
                </td>
            </tr>
            <tr>
                <th><label for="email_address">Email Address:</label></th>
                <td>
                    <input type="text" name="email_address" id="email_address"
                           value="<?php echo htmlentities($person['email_address']) ?>" size="80"/>
                </td>
            </tr>
            <tr>
                <th><label for="notes">Notes:</label></th>
                <td><textarea name="notes" rows="8" cols="80"><?php echo htmlentities($person['notes']) ?></textarea></td>
            </tr>
            <tr>
                <td></td>
                <td>
                    <?php if (!empty($person['id'])): ?>
                    <input type="hidden" name="id" value="<?php echo $person['id'] ?>" />
                    <?php endif ?>
                    <input type="submit" name="save" value="Save" />
                </td>
            </tr>
        </table>
    </form>
</div>
