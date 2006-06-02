// Set up the task list onclick handler
addEvent(window,'load',setUpTasklistTable);
function Disable(formid)
{
   document.formid.buSubmit.disabled = true;
   document.formid.submit();
}

 function showstuff(boxid){
   $(boxid).style.visibility='visible';
   $(boxid).style.display='block';
}

function hidestuff(boxid){
   $(boxid).style.visibility='hidden';
   $(boxid).style.display='none';
}

function showhidestuff(boxid) {
   switch ($(boxid).style.visibility) {
      case '': $(boxid).style.visibility='visible'; break
      case 'hidden': $(boxid).style.visibility='visible'; break
      case 'visible': $(boxid).style.visibility='hidden'; break
   }
   switch ($(boxid).style.display) {
      case '': $(boxid).style.display='block'; break
      case 'none': $(boxid).style.display='block'; break
      case 'block': $(boxid).style.display='none'; break
      case 'inline': $(boxid).style.display='none'; break
   }
}
function setUpTasklistTable() {
  if (!$('tasklist_table')) {
    // No tasklist on the page
    return;
  }
  var table = $('tasklist_table');
  addEvent(table,'click',tasklistTableClick);
}
function tasklistTableClick(e) {
  var src = eventGetSrc(e);
  if (src.nodeName != 'TD') {
    return;
  }
  if (src.hasChildNodes()) {
    var checkBoxes = src.getElementsByTagName('input');
    if (checkBoxes.length > 0) {
      // User clicked the cell where the task select checkbox is
      if (checkBoxes[0].checked) {
        checkBoxes[0].checked = false;
      } else {
        checkBoxes[0].checked = true;
      }
      return;
    }
  }
  var row = src.parentNode;
  var aElements = row.getElementsByTagName('A');
  if (aElements.length > 0) {
    window.location = aElements[0].href;
  } else {
    // If both the task id and the task summary columns are non-visible
    // just use the good old way to get to the task
    window.location = '?do=details&id=' + row.id.substr(4);
  }
}

function eventGetSrc(e) {
  if (e.target) {
    return e.target;
  } else if (window.event) {
    return window.event.srcElement;
  } else {
    return;
  }
}

function ToggleSelected(id) {
  var inputs = $(id).getElementsByTagName('input');
  for (var i = 0; i < inputs.length; i++) {
    if(inputs[i].type == 'checkbox'){
      inputs[i].checked = !(inputs[i].checked);
    }
  }
}

function addUploadFields(id) {
  if (!id) {
    id = 'uploadfilebox';
  }
  var el = $(id);
  var span = el.getElementsByTagName('span')[0];
  if ('none' == span.style.display) {
    // Show the file upload box
    span.style.display = 'inline';
    // Switch the buttons
    $(id + '_attachafile').style.display = 'none';
    $(id + '_attachanotherfile').style.display = 'inline';
    
  } else {
    // Copy the first file upload box and clear it's value
    var newBox = span.cloneNode(true);
    newBox.getElementsByTagName('input')[0].value = '';
    el.appendChild(newBox);
  }
}

function checkok(url, message, form) {

    var myAjax = new Ajax.Request(url, {method: 'get', onComplete:function(originalRequest)
	{
        if(originalRequest.responseText == 'ok' || confirm(message)) {
            $(form).submit();
        }
	}});
    return false;
}
function removeUploadField(element, id) {
  if (!id) {
    id = 'uploadfilebox';
  }
  var el = $(id);
  var span = el.getElementsByTagName('span');
  if (1 == span.length) {
    // Clear and hide the box
    span[0].style.display='none';
    span[0].getElementsByTagName('input')[0].value = '';
    // Switch the buttons
    $(id + '_attachafile').style.display = 'inline';
    $(id + '_attachanotherfile').style.display = 'none';
  } else {
    el.removeChild(element.parentNode);
  }
}

function updateDualSelectValue(id)
{
    var rt  = $('r'+id);
    var val = $('v'+id);

    val.value = '';

    var i;
    for (i=0; i < rt.options.length; i++) {
        val.value += (i > 0 ? ' ' : '') + rt.options[i].value;
    }
}

function dualSelect(from, to, id) {
    if (typeof(from) == 'string') {
        from = $(from+id);
    }
    if (typeof(to) == 'string') {
        to = $(to+id);
    }

    var i = 0;
    var opt;

    while (i < from.options.length) {
        if (from.options[i].selected) {
            opt = new Option(from.options[i].text, from.options[i].value);
            try {
                to.add(opt, null);
            }
            catch (ex) {
                to.add(opt);
            }
            from.remove(i);
            continue;
        }
        i++;
    }
    updateDualSelectValue(id);
}

function selectMove(id, step) {
    var sel = $('r'+id);

    var i = 0;

    while (i < sel.options.length) {
        if (sel.options[i].selected) {
            if (i+step < 0 || i+step > sel.options.length) {
                return;
            }
            var opt = new Option(sel.options[i].text, sel.options[i].value);
            sel.remove(i);
            try {
                sel.add(opt, sel.options[i+step]);
            }
            catch (ex) {
                sel.add(opt, i+step);
            }

            opt.selected = true;

            updateDualSelectValue(id);
            return;
        }
        i++;
    }
}
var Cookie = {
  getVar: function(name) {
    var cookie = document.cookie;
    if (cookie.length > 0) {
      cookie += ';';
    }
    re = new RegExp(name + '\=(.*?);' );
    if (cookie.match(re)) {
      return RegExp.$1;
    } else {
      return '';
    }
  },
  setVar: function(name,value,expire,path) {
    document.cookie = name + '=' + value;
  },
  removeVar: function(name) {
    var date = new Date(12);
    document.cookie = name + '=;expires=' + date.toUTCString();
  }  
};
function setUpSearchBox() {
  if ($('advancedsearch')) {
    var state = Cookie.getVar('advancedsearch');
    if ('1' == state) {
      var showState = $('advancedsearchstate');
      showState.replaceChild(document.createTextNode('+'),showState.firstChild);
      $('sc2').style.display = 'block';
    }
  }
}
function toggleSearchBox(themeurl) {
  var state = Cookie.getVar('advancedsearch');
  if ('1' == state) {
      $('advancedsearchstateimg').src = themeurl + 'edit_add.png';
      hidestuff('sc2');  
      Cookie.setVar('advancedsearch','0');
  } else {
      $('advancedsearchstateimg').src = themeurl + 'edit_remove.png';
      showstuff('sc2'); 
      Cookie.setVar('advancedsearch','1');
  }
}
function deletesearch(id, url) {
    var img = $('rs' + id).getElementsByTagName('img')[0].src = url + 'themes/Bluey/ajax_load.gif';
    url = url + 'javascript/callbacks/deletesearches.php';
    var myAjax = new Ajax.Request(url, {method: 'get', parameters: 'id=' + id,
                     onSuccess:function()
                     {
                        var oNodeToRemove = $('rs' + id);
                        oNodeToRemove.parentNode.removeChild(oNodeToRemove);
                        var table = $('mysearchestable');
                        if(table.rows.length > 0) {
                            table.getElementsByTagName('tr')[table.rows.length-1].style.borderBottom = '0';
                        } else {
                            showstuff('nosearches');
                        }
                     }
                });
}
function savesearch(query, baseurl, savetext) {
    url = baseurl + 'javascript/callbacks/savesearches.php?' + query + '&search_name=' + $('save_search').value;
    if($('save_search').value != '') {
        var old_text = $('lblsaveas').firstChild.nodeValue;
        $('lblsaveas').firstChild.nodeValue = savetext;
        var myAjax = new Ajax.Request(url, {method: 'get',
                     onComplete:function()
                     {
                        $('lblsaveas').firstChild.nodeValue=old_text;
                        var myAjax2 = new Ajax.Updater('mysearches', baseurl + 'javascript/callbacks/getsearches.php', { method: 'get'});
                     }
                     });
    }
}
function activelink(id) {
    if($(id).className == 'active') {
        $(id).className = 'inactive';
    } else {
        $(id).className = 'active';
    }
}
var useAltForKeyboardNavigation = false;  // Set this to true if you don't want to kill
                                         // Firefox's find as you type 

function getVoters(id, baseurl, field)
{
    var url = baseurl + 'javascript/callbacks/getvoters.php?id=' + id;
    var myAjax = new Ajax.Updater(field, url, { method: 'get'});
}
function emptyElement(el) {
    while(el.firstChild) {
        emptyElement(el.firstChild);
        var oNodeToRemove = el.firstChild;
        oNodeToRemove.parentNode.removeChild(oNodeToRemove);
    }
}
function showPreview(textfield, baseurl, field)
{
    var preview = $('preview');
    emptyElement(preview);
    
    var img = document.createElement('img');
    img.src = baseurl + 'themes/Bluey/ajax_load.gif';
    img.id = 'temp_img';
    img.alt = 'Loading...';
    preview.appendChild(img);
    
    var text = new String($(textfield).value);
    text = text.replace(/&/g,'%26');
    text = text.replace(/=/g,'%3D');
    var url = baseurl + 'javascript/callbacks/getpreview.php';
    var myAjax = new Ajax.Updater(field, url, {parameters:'text=' + text, method: 'post'});

    if (text == '') {
        hidestuff(field);
    } else {
        showstuff(field);
    }
}
function checkname(value){
    new Ajax.Request('javascript/callbacks/searchnames.php?name='+value, {onSuccess: function(t){ allow(t.responseText); } });
}
function allow(booler){
    if(booler.indexOf('false') > -1) {
        $('username').style.color ='red';
        $('buSubmit').style.visibility = 'hidden';
        $('errormessage').innerHTML = booler.substring(6,booler.length);
    }
    else {
        $('username').style.color ='green';
        $('buSubmit').style.visibility = 'visible';
        $('errormessage').innerHTML = '';
    }  
}
function getHistory(id, baseurl, field, details)
{
    var url = baseurl + 'javascript/callbacks/gethistory.php?id=' + id;
    if (details) {
        url += '&details=' + details;
    }
    var myAjax = new Ajax.Updater(field, url, { method: 'get'});
}

/*********  Permissions popup  ***********/

function createClosure(obj, method) {
    return (function() { obj[method](); });
}

function Perms(id) {
    this.div = $(id);
}

Perms.prototype.timeout = null;
Perms.prototype.div     = null;

Perms.prototype.clearTimeout = function() {
    if (this.timeout) {
        clearTimeout(this.timeout);
        this.timeout = null;
    }
}

Perms.prototype.do_later = function(action) {
    this.clearTimeout();
    closure = createClosure(this, action);
    this.timeout = setTimeout(closure, 400);
}

Perms.prototype.show = function() {
    this.clearTimeout();
    this.div.style.display = 'block';
    this.div.style.visibility = 'visible';
}

Perms.prototype.hide = function() {
    this.clearTimeout();
    this.div.style.display = 'none';
}