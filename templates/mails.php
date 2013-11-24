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
                    <?= _("Anzahl zu sendender Kommentare an Sie") ?>
                    <dfn><?= _("Sie bekommen Blubbermails ähnlich wie Notifications immer nur, wenn Sie selbst etwas geschrieben haben. Aber wie lange? Wollen Sie nur einmal über einen neuen Blubberkommentar per Mail benachrichtigt werden oder nur die ersten 10 mal oder immer, nachdem Sie selbst was geschrieben haben?") ?></dfn>
                </label>
            </td>
            <td>
                <select>
                    <option value="0"><?= _("keine") ?></option>
                    <option value="">1</option>
                    <option value="">2</option>
                    <option value="">3</option>
                    <option value="">4</option>
                    <option value="">5</option>
                    <option value="">10</option>
                    <option value="">15</option>
                    <option value="">20</option>
                    <option value="">30</option>
                    <option value="">40</option>
                    <option value="">50</option>
                    <option value="">100</option>
                    <option value="all" selected><?= _("alle") ?></option>
                </select>
            </td>
        </tr>
        <tr>
            <td>
                <?= _("Benachrichtigen über Blubberstreams") ?>
                <dfn><?= _("Oben haben Sie eingestellt, welche und wieviele Kommentare zu Blubbern Sie bekommen wollen. Hier stellen Sie ein, welche Hauptblubber (Threads) Ihnen per Mail zugeschickt werden. Sie können eigene Blubberstreams zusammen stellen, deren Hauptblubber Ihnen per Mail gesendet werden, oder Sie wählen einfach den globalen Blubber aus.") ?></dfn>
            </td>
            <td>
                <div>
                    <label>
                        <input type="checkbox" name="streams[]" value="global">
                        <?= _("Globaler Stream") ?>
                    </label>
                </div>
            </td>
        </tr>
    </tbody>
</table>