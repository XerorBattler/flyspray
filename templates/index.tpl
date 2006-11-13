<?php if(isset($update_error)): ?>
<div id="updatemsg">
	<span class="bad"> {L('updatewrong')}</span>
	<a href="?hideupdatemsg=yep">{L('hidemessage')}</a>
</div>
<?php endif; ?>

<?php if(isset($updatemsg)): ?>
<div id="updatemsg">
    <a href="http://flyspray.rocks.cc/">{L('updatefs')}</a> {L('currentversion')}
    <span class="bad">{$fs->version}</span> {L('latestversion')} <span class="good">{$_SESSION['latest_version']}</span>.
    <a href="?hideupdatemsg=yep">{L('hidemessage')}</a>
</div>
<?php endif; ?>

<?php if (!($user->isAnon() && count($fs->projects) == 0)): ?>
<div id="search">
  <map id="projectsearchform" name="projectsearchform">
    <form action="{$_SERVER['PHP_SELF']}" method="get">
      <div>
        <button type="submit">{L('searchthisproject')}</button>
        <input class="text" id="searchtext" name="string" type="text" size="20"
               maxlength="100" value="{Get::val('string')}" accesskey="q" />
        
        <input type="hidden" name="project" value="{Get::num('project', 0)}" />
        <?php if (!$user->isAnon()): ?>
        <span class="save_search"><label for="save_search" id="lblsaveas">{L('saveas')}</label>
        <input class="text" type="text" value="{Get::val('search_name')}" id="save_search" name="search_name" size="15" />
        &nbsp;<button onclick="savesearch('{$_SERVER['QUERY_STRING']}', '{$baseurl}', '{L('saving')}')" type="button">{L('OK')}</button>
        <?php if ($user->didSearch()): ?>
        <button type="submit" name="reset" value="1">{L('reset')}</button>
        <?php endif; ?>
        </span>
        <?php endif; ?>
        
        <span id="searchstate" style="cursor:pointer">
        <a onclick="toggleSearchBox('{$this->themeUrl()}');return false;" href="{CreateUrl('project', $proj->id, null, array_merge($_GET, array('toggleadvanced' => 1)))}"><span id="advancedsearchstate" class="showstate">
        <img id="advancedsearchstateimg" src="<?php echo (Cookie::val('advancedsearch')) ? $this->get_image('edit_remove') : $this->get_image('edit_add'); ?>"
             alt="<?php echo (Cookie::val('advancedsearch')) ? '-' : '+'; ?>" width="16" height="16" />
        </span>{L('advanced')}</a>
        </span>
        
        <div id="sc2" class="switchcontent" <?php if (!Cookie::val('advancedsearch')):?>style="display:none;"<?php endif; ?> >
        <fieldset><legend>{L('miscellaneous')}</legend>
        {!tpl_checkbox('search_in_comments', Get::has('search_in_comments'), 'sic')}
        <label class="left" for="sic">{L('searchcomments')}</label>
        
        {!tpl_checkbox('search_for_all', Get::has('search_for_all'), 'sfa')}
        <label class="left" for="sfa">{L('searchforall')}</label>

        {!tpl_checkbox('only_watched', Get::has('only_watched'), 'only_watched')}
        <label class="left" for="only_watched">{L('taskswatched')}</label>
        
        {!tpl_checkbox('only_primary', Get::has('only_primary'), 'only_primary')}
        <label class="left" for="only_primary">{L('onlyprimary')}</label>
        
        {!tpl_checkbox('has_attachment', Get::has('has_attachment'), 'has_attachment')}
        <label class="left" for="has_attachment">{L('hasattachment')}</label>
        
        </fieldset>

        <fieldset><legend>{L('taskproperties')}</legend>
        
        <div class="search_select">
        <label class="default multisel" for="type">{L('tasktype')}</label>
        <select name="type[]" id="type" multiple="multiple" size="5">
          {!tpl_options(array('' => L('alltasktypes')) + $proj->listTaskTypes(), Get::val('type', ''))}
        </select>
        </div>
        
        <div class="search_select">
        <label class="default multisel" for="sev">{L('severity')}</label>
        <select name="sev[]" id="sev" multiple="multiple" size="5">
          {!tpl_options(array('' => L('allseverities')) + $fs->severities, Get::val('sev', ''))}
        </select>
        </div>
        
        <div class="search_select">
        <label class="default multisel" for="pri">{L('priority')}</label>
        <select name="pri[]" id="pri" multiple="multiple" size="5">
          {!tpl_options(array('' => L('allpriorities')) + $fs->priorities, Get::val('pri', ''))}
        </select>
        </div>
        
        <div class="search_select">
        <label class="default multisel" for="due">{L('dueversion')}</label>
        <select name="due[]" id="due" multiple="multiple" size="5">
          {!tpl_options(array_merge(array('' => L('dueanyversion'), 0 => L('unassigned')), $proj->listVersions(false)), Get::val('due', ''))}
        </select>
        </div>
        
        <div class="search_select">
        <label class="default multisel" for="reported">{L('reportedversion')}</label>
        <select name="reported[]" id="reported" multiple="multiple" size="5">
          {!tpl_options(array('' => L('anyversion')) + $proj->listVersions(false), Get::val('reported', ''))}
        </select>
        </div>
        
        <div class="search_select">
        <label class="default multisel" for="cat">{L('category')}</label>
        <select name="cat[]" id="cat" multiple="multiple" size="5">
          {!tpl_options(array('' => L('allcategories')) + $proj->listCategories(), Get::val('cat', ''))}
        </select>
        </div>

        <div class="search_select">
        <label class="default multisel" for="status">{L('status')}</label>
        <select name="status[]" id="status" multiple="multiple" size="5">
          {!tpl_options(array('' => L('allstatuses')) +
                        array('open' => L('allopentasks')) +
                        array('closed' => L('allclosedtasks')) +
                        $proj->listTaskStatuses(), Get::val('status', 'open'))}
        </select>
        </div>
        
        <div class="search_select">
        <label class="default multisel" for="percent">{L('percentcomplete')}</label>
        <select name="percent[]" id="percent" multiple="multiple" size="5">
          <?php $percentages = array(); for ($i = 0; $i <= 100; $i += 10) $percentages[$i] = $i; ?>
          {!tpl_options(array('' => L('anyprogress')) + $percentages, Get::val('percent', ''))}
        </select>
        </div>
        <div class="clear"></div>
        </fieldset>

        <fieldset><legend>{L('users')}</legend>
        <label class="default multisel" for="opened">{L('openedby')}</label>
        {!tpl_userselect('opened', Get::val('opened'), 'opened')}

        <label class="default multisel" for="dev">{L('assignedto')}</label>
        {!tpl_userselect('dev', Get::val('dev'), 'dev')}
        
        <label class="default multisel" for="closed">{L('closedby')}</label>
        {!tpl_userselect('closed', Get::val('closed'), 'closed')}

        </fieldset>
        
        <fieldset><legend>{L('dates')}</legend>
        <div class="dateselect">
          {!tpl_datepicker('duedatefrom', L('selectduedatefrom'))}
          {!tpl_datepicker('duedateto', L('selectduedateto'))}
        </div>
        
        <div class="dateselect">
          {!tpl_datepicker('changedfrom', L('selectsincedatefrom'))}
          {!tpl_datepicker('changedto', L('selectsincedateto'))}
        </div>
        
        <div class="dateselect">
          {!tpl_datepicker('openedfrom', L('selectopenedfrom'))}
          {!tpl_datepicker('openedto', L('selectopenedto'))}
        </div>
        
        <div class="dateselect">
          {!tpl_datepicker('closedfrom', L('selectclosedfrom'))}
          {!tpl_datepicker('closedto', L('selectclosedto'))}
        </div>
        </fieldset>

       </div>
       <input type="hidden" name="do" value="index" />
      </div>
    </form>
  </map>
</div>
<?php endif; ?>

<div id="tasklist">
  <form action="{$_SERVER['PHP_SELF']}" id="massops" method="post">
    <div>
      <table id="tasklist_table">
        <thead>
          <tr>
            <th class="caret">
            </th>
            <?php if (!$user->isAnon()): ?>
            <th class="ttcolumn">
                <?php if (!$user->isAnon() && $total): ?>
                <a href="javascript:ToggleSelected('massops')">
                  <img alt="{L('toggleselected')}" title="{L('toggleselected')}" src="{$this->get_image('kaboodleloop')}" width="16" height="16" />
                </a>
                <?php endif; ?>
            </th>
            <?php endif; ?>
            <?php foreach ($visible as $col): ?>
            {!tpl_list_heading($col, "<th%s>%s</th>")}
            <?php endforeach; ?>
          </tr>
        </thead>
        <tbody>
        <?php foreach ($tasks as $task_details):?>
        <tr id="task{!$task_details['task_id']}" class="severity{$task_details['task_severity']}">
          <td class="caret">
          </td>
          <?php if (!$user->isAnon()): ?>
          <td class="ttcolumn">
            <input class="ticktask" type="checkbox" name="ids[]" value="{$task_details['task_id']}" />
          </td>
          <?php endif; ?>
          <?php foreach ($visible as $col): ?>
          {!tpl_draw_cell($task_details, $col)}
          <?php endforeach; ?>
        </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
      <table id="pagenumbers">
        <tr>
          <?php if ($total): ?>
          <td id="taskrange">
            {!sprintf(L('taskrange'), $offset + 1,
              ($offset + $perpage > $total ? $total : $offset + $perpage), $total)}
          </td>
          <td id="numbers">
            {!pagenums($pagenum, $perpage, $total)}
          </td>
          <?php else: ?>
          <td id="taskrange"><strong>{L('noresults')}</strong></td>
          <?php endif; ?>
        </tr>
        <?php if (!$user->isAnon() && $total): ?>
        <tr id="massopsactions">
          <td>
            <select name="action">
              <option value="details.add_notification">{L('watchtasks')}</option>
              <option value="remove_notification">{L('stopwatchingtasks')}</option>
              <option value="takeownership">{L('assigntaskstome')}</option>
            </select>
            <input type="hidden" name="user_id" value="{$user->id}" />
            <button type="submit">{L('takeaction')}</button>
          </td>
          <td id="export">
            <a href="{$baseurl}?{Url::query_from_array(array_merge($_GET, array('do' => 'export')))}">
              <img alt="{L('csvexport')}" title="{L('csvexport')}" src="{$this->get_image('csvexport')}" width="16" height="16" /> {L('csvexport')}
            </a>
          </td>
        </tr>
        <?php endif ?>
      </table>
    </div>
  </form>
</div>
