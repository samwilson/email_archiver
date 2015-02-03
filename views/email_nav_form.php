
<table>
    <tr>
        <th><span class="latex">L<sup>a</sup>T<sub>e</sub>X</span></th>
        <td>
            <?php foreach ($years as $y): ?>
            <a href="latex.php?year=<?php echo $y ?>"><?php echo $y ?></a> &middot;
            <?php endforeach ?>
        </td>
    </tr>
    <tr>
        <th><span class="latex">Chronological</span></th>
        <td>
            <?php foreach ($years as $y): ?>
            <a href="index.php?year=<?php echo $y ?>"><?php echo $y ?></a> &middot;
            <?php endforeach ?>
        </td>
    </tr>
</table>

<form action="index.php#reply-form" method="get">
    <p class="centre">
        <?= number_format($email_count) ?> emails in archive.
        <select name="with">
            <?php foreach ($people as $id => $name): ?>
            <option value="<?php echo $id ?>"<?php if ($with==$id) echo ' selected' ?>>
                <?php echo $name ?>
            </option>
            <?php endforeach ?>
        </select>
        <select name="year">
            <?php foreach ($years as $y): ?>
            <option value="<?php echo $y ?>"<?php if ($year==$y) echo ' selected' ?>>
                <?php echo $y ?>
            </option>
            <?php endforeach ?>
        </select>
        <input type="submit" value="View" />
    </p>
</form>
