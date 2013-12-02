<form action="?" method="post">
<table id="main_content" class="default">
    <colgroup>
        <col width="50%">
        <col width="50%">
    </colgroup>
    <caption><?= _("Blubbermails") ?></caption>
    <thead>
        <tr>
            <th><?= _("Element") ?></th>
            <th><?= _("Einstellung") ?></th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td>
                <label>
                    <?= _("Anzahl zu sendender Kommentare an Sie pro Thread") ?>
                    <dfn><?= _("Sie bekommen Blubbermails ähnlich wie Notifications immer nur, wenn Sie selbst etwas geschrieben haben und dann antwortet jemand darauf. Aber wie oft? Wollen Sie nur einmal über einen neuen Blubberkommentar per Mail benachrichtigt werden oder nur die ersten 10 mal oder immer, nachdem Sie selbst was geschrieben haben?") ?></dfn>
                </label>
            </td>
            <td>
                <? $default = UserConfig::get($GLOBALS['user']->id)->getValue("BLUBBER_MAX_USER_NOTIFICATIONS") ?>
                <select name="BLUBBER_MAX_USER_NOTIFICATIONS">
                    <option value="0"><?= _("keine") ?></option>
                    <option value="1"<?= $default == "1" ? " selected" : "" ?>>1</option>
                    <option value="2"<?= $default == "2" ? " selected" : "" ?>>2</option>
                    <option value="3"<?= $default == "3" ? " selected" : "" ?>>3</option>
                    <option value="4"<?= $default == "4" ? " selected" : "" ?>>4</option>
                    <option value="5"<?= $default == "5" ? " selected" : "" ?>>5</option>
                    <option value="10"<?= $default == "10" ? " selected" : "" ?>>10</option>
                    <option value="15"<?= $default == "15" ? " selected" : "" ?>>15</option>
                    <option value="20"<?= $default == "20" ? " selected" : "" ?>>20</option>
                    <option value="30"<?= $default == "30" ? " selected" : "" ?>>30</option>
                    <option value="40"<?= $default == "40" ? " selected" : "" ?>>40</option>
                    <option value="50"<?= $default == "50" ? " selected" : "" ?>>50</option>
                    <option value="100"<?= $default == "100" ? " selected" : "" ?>>100</option>
                    <option value="all"<?= $default === "all" || $default === null ? " selected" : "" ?>><?= _("alle") ?></option>
                </select>
            </td>
        </tr>
        <? if (version_compare($GLOBALS['SOFTWARE_VERSION'], "2.5.99", ">")) : ?>
        <tr>
            <td>
                <?= _("Benachrichtigen über Blubberstreams") ?>
                <dfn><?= _("Oben haben Sie eingestellt, welche und wieviele Kommentare zu Blubbern Sie bekommen wollen. Hier stellen Sie ein, welche Hauptblubber (Threads) Ihnen per Mail zugeschickt werden. Sie können eigene Blubberstreams zusammen stellen, deren Hauptblubber Ihnen per Mail gesendet werden, oder Sie wählen einfach den globalen Blubber aus.") ?></dfn>
            </td>
            <td>
                <div>
                    <label>
                        <input type="checkbox" name="streams[]" value="global"<?= in_array("global", $abo_streams) ? " checked" : "" ?>>
                        <?= _("Globaler Stream") ?>
                    </label>
                </div>
                <? foreach ($streams as $stream) : ?>
                <div>
                    <label>
                        <input type="checkbox" name="streams[]" value="<?= htmlReady($stream->getId()) ?>"<?= in_array($stream->getId(), $abo_streams) ? " checked" : "" ?>>
                        <?= htmlReady($stream['name']) ?>
                    </label>
                </div>
                <? endforeach ?>
                <div>
                    <a href="<?= URLHelper::getLink("plugins.php/blubber/streams/edit") ?>"><?= _("Benutzerdefinierten Stream erstellen.") ?></a>
                </div>
            </td>
        </tr>
        <? endif ?>
        <? if (get_config("BLUBBERMAIL_CREATE_THREADS_ALLOWED")) : ?>
        <tr>
            <td><?= _("Öffentliche Blubber per Mail schreiben") ?></td>
            <td><?= htmlReady(MailProcessor::getInstance()->getReplyMail("public")) ?></td>
        </tr>
        <tr>
            <td><?= _("Privaten Blubber per Mail schreiben") ?></td>
            <td><?= htmlReady(MailProcessor::getInstance()->getReplyMail("private")) ?></td>
        </tr>
        <? endif ?>
    </tbody>
    <tfoot>
        <tr>
            <td colspan="2">
                <?= \Studip\Button::create(_("speichern"))?>
            </td>
        </tr>
    </tfoot>
</table>
</form>