<h3>{$proj->prefs['project_title']} :: {L('newtask')}</h3>

<div id="taskdetails">
    <form enctype="multipart/form-data" action="{$this->url(array('newtask', 'proj' . $proj->id))}" method="post">
    <h2 class="task {$fs->GetColorCssClass($_POST)} summary" id="edit_summary">
      <label for="itemsummary">{L('summary')}</label>
      <input id="itemsummary" class="text task {$fs->GetColorCssClass($_POST)}" type="text" value="{Req::val('item_summary')}"
        name="item_summary" size="80" maxlength="100" />
    </h2>

    <table><tr><td id="taskfieldscell"><?php // small layout table ?>

    <div id="taskfields">
      <table>
        <?php foreach ($proj->fields as $field): ?>
        <tr>
          <th id="f{$field->id}">{$field->prefs['field_name']}</th>
          <td headers="f{$field->id}">
            {!$field->edit(USE_DEFAULT, LOCK_FIELD)}
          </td>
        </tr>
        <?php endforeach; ?>
        <?php if ($user->perms('modify_all_tasks')): ?>
        <tr>
          <td>
            <label for="assigned_to">{L('assignedto')}</label>
          </td>
          <td>
            <?php if ($user->perms('modify_all_tasks')): ?>
            <?php $this->display('common.multiuserselect.tpl'); ?>
            <?php endif; ?>
          </td>
        </tr>
        <?php endif; ?>
        <tr>
          <td><label for="private">{L('confidential')}</label></td>
          <td>
            {!tpl_checkbox('mark_private', Req::val('mark_private', 0), 'private')}
          </td>
        </tr>
      </table>
    </div>

    </td><td style="width:100%">

    <div id="taskdetailsfull">
      <h3 class="taskdesc">{L('details')}</h3>
      {!$this->text->textarea('detailed_desc', 15, 80, array(), Req::val('detailed_desc', $proj->prefs['default_task']))}
      <?php if ($user->perms('create_attachments')): ?>
      <div id="uploadfilebox">
        <span style="display: none"><?php // this span is shown/copied in javascript when adding files ?>
          <input tabindex="5" class="file" type="file" size="55" name="userfile[]" />
            <a href="javascript://" tabindex="6" onclick="removeUploadField(this, 'uploadfilebox');">{L('remove')}</a><br />
        </span>
        <noscript>
          <span>
            <input tabindex="5" class="file" type="file" size="55" name="userfile[]" />
              <a href="javascript://" tabindex="6" onclick="removeUploadField(this, 'uploadfilebox');">{L('remove')}</a><br />
          </span>
        </noscript>
      </div>
      <button id="uploadfilebox_attachafile" tabindex="7" type="button" onclick="addUploadFields('uploadfilebox')">
        {L('uploadafile')} ({L('max')} {$fs->max_file_size} {L('MiB')})
      </button>
      <button id="uploadfilebox_attachanotherfile" tabindex="7" style="display: none" type="button" onclick="addUploadFields('uploadfilebox')">
         {L('attachanotherfile')} ({L('max')} {$fs->max_file_size} {L('MiB')})
      </button>
      <?php endif; ?>

    <p>
        <?php if ($user->isAnon()): ?>
        <label class="inline" for="anon_email">{L('youremail')}</label><input type="text" class="text" id="anon_email" name="anon_email" size="30"  value="{Req::val('anon_email')}" /><br />

    <?php
        if($fs->prefs['use_recaptcha']) {
            $captcha = new reCAPTCHA_Challenge();
            $captcha->publickey = $fs->prefs['recaptcha_public_key'];
			$captcha->use_ssl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on');
            echo $captcha->getChallenge();
         }
    ?>

        <?php endif; ?>
        <input type="hidden" name="action" value="newtask" />
        <input type="hidden" name="project_id" value="{$proj->id}" />
        <button accesskey="s" type="submit">{L('addthistask')}</button>
    </p>
    <?php if (!$user->isAnon()): ?>
    <div>
      <input type="checkbox" id="notifyme" name="notifyme"
      value="1" checked="checked" />&nbsp;<label class="inline left" for="notifyme">{L('notifyme')}</label><br />
      {!tpl_checkbox('more_tasks', Req::val('more_tasks', 0), 'more_tasks')}<label class="inline left" for="more_tasks">
      {L('addmoretasks')}</label>
    </div>
    <?php endif; ?>
    </div>

    </td></tr></table>

  </form>

  <div class="clear"></div>
</div>