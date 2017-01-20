let pageEditor = {

  //  ============================================
  //  SETUP
  //  ============================================
  "currHref":'',
  "pageEditorPreview":'',
  init(){
    pageEditor.currHref = $(location).attr('href').replace(/[^\/]*$/, '');
    pageEditor.pageEditorPreview = $("#pageEditorPreview");
    pageEditor.pageEditorPreview.on('load', function(){
      pageEditor.findElements();
      pageEditor.addIframeHandler();
      pageEditor.page_plugins();
    });
  },

  //  ============================================
  //  Open in new Window
  //  ============================================

  openWindowPreview(){
    let insideContainer = $('.pageGenerator .preview');
    let menuContainer = $('.pageGenerator .menu');
    let pageEditorWindow = $(window.open("", "MsgWindow", "width=1200,height=800"));
    let head = $(pageEditorWindow[0].document.head);
    head.append(`<link rel='stylesheet' href='${pageEditor.currHref}css/content.css' type='text/css' media='screen'>`);
    let body = $(pageEditorWindow[0].document.body);
    body.css('overflow', 'hidden');
    body.css('margin', 0);
    body.css('padding', 0);
    body.append(pageEditor.pageEditorPreview);
    insideContainer.fadeOut(200, function(){
      menuContainer.css('transition', "ease-in 0.2s");
      menuContainer.css('width', "100%");
    });
    $(pageEditorWindow).on('unload', function(){
      insideContainer.append(pageEditor.pageEditorPreview);
      menuContainer.css('transition', "ease-in 0.2s");
      menuContainer.css('width', "30%");
      setTimeout(function(){insideContainer.fadeIn(200);}, 300);
    });
  },

  //  ============================================
  //  PageEditor Save
  //  ============================================

  save(callback){
    pageEditor.findIframeElements();

    var plugins = [];
    $(pageEditor.pageEditorPlugins).each(function(){
      var pluginInfo = {}
      pluginInfo['id'] = $(this).data('id');
      pluginInfo['plugin'] = $(this).data('plugin');
      pluginInfo['area'] = pageEditor.getPluginArea(this);
      pluginInfo['settings'] = pageEditor.getPluginSettings(this);
      plugins.push(pluginInfo);
    });

    var data = {
      "url": pageEditor.getUrl(),
      "plugins":plugins
    };

    $.ajax({
      dataType: "json",
      method: "POST",
      url: 'php/ajax_pageEditor.php?a=save',
      data: data,
      success: function(data){
        if(data.error){
          callback(false, data.data);
        }else{
          callback(true, null);
        }
      },
      error: function(){
        callback(false, null);
      }
    });
  },

  //  ============================================
  //  SIDEPAGE
  //  ============================================

  page_settings(plugin, pluginid, callback, settings){
    var currSettings = (settings == null) ? [] : settings;
    pageEditor.pageEditorSidePage.animate({'opacity':0}, 500, function(){
      var html = "";
      $.post('php/ajax_pageEditor.php?a=pluginSettings', {'plugin':encodeURIComponent(plugin),'id':encodeURIComponent(pluginid)}, function(data){
        if(data.error == false){

          // Add all the Settings Input fields and handle if there are no settings
          if(data.data != null && data.data.length > 0){
            data.data.forEach(function(setting){
              var value = pageEditor.getSettingsValue(currSettings,setting.key);
              html += pageEditor.addSettingInput(setting,value);
            });
          }else{
            callback(true, []);
            return;
          }

          // Create Submit and Cancel Button
          html += '<button class="oxbutton settings-save">Save</button>';
          html += '<button class="oxbutton settings-cancel">Cancel</button>';

        }

        //  ADD HTML
        pageEditor.pageEditorSidePage.html(html);

        // ADD HANDLER
        pageEditor.page_addSettingHandler(pageEditor.pageEditorSidePage)
        pageEditor.pageEditorSidePage.find('.addListItem').on('click', function(){
          let parent = $(this).parent();
          let key = parent.data('key');
          let type = parent.data('type');
          let html = pageEditor.createItemList(key, pageEditor.getItemListNr(parent), type);
          let element = $(html).insertBefore($(this));
          pageEditor.page_addSettingHandler(element);
        });
        pageEditor.pageEditorSidePage.find('.settings-save').on('click', function(){
          callback(true, pageEditor.getSettingData());
        });
        pageEditor.pageEditorSidePage.find('.settings-cancel').on('click', function(){
          callback(false, null);
        });

        pageEditor.pageEditorSidePage.animate({'opacity':1}, 500,function(){
          // loaded
        });
      }, "json");
    });
  },

  page_addSettingHandler(item){
    item.find("input[data-oxytype='bool']").each(function(){
      $(this).on('change', function(){
        let val = ($(this).prop( "checked" )) ? 1 : 0;
        $(this).val(val);
      });
    });
    item.find("input[data-oxytype='file']").each(function(){
      fileSelector.init(this);
    });
    item.find("button.deleteItem").click(function(){
      this.parentElement.remove();
    });
  },

  page_plugins(){
    pageEditor.pageEditorSidePage.animate({'opacity':0}, 500,function(){
      var html = '<div class="plugins">';
      $.getJSON("php/ajax_pageEditor.php?a=getPlugins", function(data){
        if(data.error == false){

          // list all plugins
          data.data.forEach(function(plugin){
            html += '<div data-name="'+plugin.name+'" draggable="true" class="plugin"><div class="name">' + plugin.config.displayname + '</div>';
            if(plugin.thumb == true){
              html += '<div class="thumb" style="background-image:url(../' + plugin.thumbUrl + ')">&nbsp;</div>';
            }
            html += '</div>';
          });

        }

        html += '</div>';
        pageEditor.pageEditorSidePage.html(html);
        pageEditor.addMenuPluginHandler();
        pageEditor.pageEditorSidePage.animate({'opacity':1}, 500,function(){
          // loaded
        });
      });
    });
  },

  getItemListNr(list){
    let id = 0;
    let items = list.find('.setting');
    let freeFound = false;
    while(!freeFound){
      freeFound = true;
      items.each(function(){
        if(id == $(this).data('listitemid')) freeFound = false;
      });
      if(!freeFound) id++;
    }
    return id;
  },

  addSettingInput(setting, value, list, countingListItemId){
    value = (value == null) ? "" : value;
    list = (list == null) ? "" : list;
    countingListItemId = (countingListItemId == null) ? "" : countingListItemId;

    var isList = Object.prototype.toString.call(setting.type) === '[object Array]';
    var addClass = (isList) ? " list" : "";

    var html = '<div class="setting'+addClass+'" data-listitemid="'+countingListItemId+'" data-list="'+list+'" data-key="'+setting.key+'" data-type="'+(isList ? JSON.stringify(setting.type).escapeHtml() : setting.type)+'">';
    html += '<h2 class="oxlabel'+addClass+'">'+setting.displayname+'</h2>';
    html += '<p class="oxdescription'+addClass+'">'+setting.description+'</p>';

    // IF LIST
    if(isList){

      value = (Object.prototype.toString.call(value) === '[object Array]') ? value : [];
      var listNr = 0;
      value.forEach(function(val){
        html += pageEditor.createItemList(setting.key, listNr++, setting.type, val);
      });

      html += '<button class="oxbutton rightBlock addListItem">Add</button>';

    }else{

      value = $("<div>").text(value).html();
      value = value.replace(/["']/g, "&quot;");
      switch(setting.type) {
        case 'textarea':
        // escape value
        html += '<textarea class="settingbox oxinput">'+value+'</textarea>';
        break;
        case 'file':
        // escape value
        html += '<input class="settingbox oxinput" data-oxytype="file" type="text" value="'+value+'"></input>';
        break;
        case 'bool':
        // escape value
        html += '<input class="settingbox oxinput" data-oxytype="bool" type="checkbox"'+(value==1 ? " checked" : "")+' value="'+value+'"></input>';
        break;
        case 'text':
        default:
        // escape value
        html += '<input class="settingbox oxinput" type="text" value="'+value+'"></input>';
      }

    }

    html += "</div><br>";
    return html;
  },

  createItemList(listkey, listNr, items, values){
    values = (values) ? values : [];
    var html = "";
    html +='<div class="itemlist">';
    html += '<button class="deleteItem"><i class="fa fa-times" aria-hidden="true"></i></button>';
    items.forEach(function(input){
      let val = pageEditor.getSettingsValue(values, input.key);
      html += pageEditor.addSettingInput(input, val, listkey, listNr);
    });
    html += '</div>';
    return html;
  },

  getSettingData(){
    var settings = [];
    pageEditor.pageEditorSidePage.find('.setting').each(function(index){
      let setting = $(this);
      var keyValueObject = {
        "settingkey":setting.data('key'),
        "settingtype":setting.data('type'),
        "settingvalue":null
      };
      // If list than type list
      keyValueObject.settingtype = (Object.prototype.toString.call(keyValueObject.settingtype) === '[object Array]') ? "list" : keyValueObject.settingtype;

      switch(setting.data('type')) {
        case 'textarea':
        keyValueObject.settingvalue = setting.find('.settingbox').val();
        break;
        case 'text':
        default:
        keyValueObject.settingvalue = setting.find('.settingbox').val();
      }

      if(setting.data('list') != ""){
        settings = pageEditor.getSettingDataPushToList(settings, setting.data('list'), setting.data('listitemid'), keyValueObject);
      }else{
        settings.push(keyValueObject);
      }
    });

    return settings;
  },

  getSettingDataPushToList(haystack, list, listitemid, valuePair){
    let found = false;
    haystack.map(function(item){
      if(item.settingkey === list){
        if(Object.prototype.toString.call(item.settingvalue) !== '[object Array]'){
          item.settingvalue = [];
        }
        found = true;
      }
      return item;
    });
    if(!found){
      haystack.push({
        "settingkey":list,
        "settingtype":'list', // <= added this line, not tested.. :)
        "settingvalue":[]
      });
    }
    haystack.map(function(item){
      if(item.settingkey === list){
        if(!item.settingvalue[listitemid]) item.settingvalue[listitemid] = [];
        item.settingvalue[listitemid].push(valuePair);
      }
      return item;
    });
    return haystack;
  },


  findElements(){
    // PREVIEW IFRAME STUFF
    pageEditor.findIframeElements();
    // LIGHTBOX STUFF
    pageEditor.pageEditorSidePage = lightboxDialog.contents().find('.menu');
  },

  'pageEditorAreas':null,
  'pageEditorPlugins':null,
  findIframeElements(){
    pageEditor.pageEditorAreas = pageEditor.pageEditorPreview.contents().find('.oxymora-area');
    pageEditor.pageEditorPlugins = pageEditor.pageEditorPreview.contents().find(".oxymora-plugin[data-deleted!=true]");
  },


  //  ============================================
  //  HANDLER
  //  ============================================
  lastDraggedPlugin: null,

  addMenuPluginHandler(){
    pageEditor.pageEditorSidePage.find('.plugin').on('dragstart', pageEditor.menu_plugin_dragstartHandler);
    pageEditor.pageEditorSidePage.find('.plugin').on('dragend', pageEditor.menu_plugin_dragendHandler);
  },

  menu_plugin_dragstartHandler(){
    pageEditor.lastDraggedPlugin = $(this);
    $(this).css("border-color","rgb(255, 0, 168)");
    $(this).find('.name').css("color","rgb(255, 140, 240)");
  },

  menu_plugin_dragendHandler(){
    $(this).css("border-color","rgb(11, 118, 224)");
    $(this).find('.name').css("color","white");
  },


  //  ============================================
  //  IFRAME HANDLER
  //  ============================================
  dropTarget: null,
  dropIsActive: null,
  addIframeHandler(){
    // IFrame Handler
    pageEditor.pageEditorPreview.contents().find('html').on('drop', pageEditor.iframe_dropHandler);

    // Area Handler
    pageEditor.pageEditorAreas.each(function(){
      $(this).on('dragleave', function(e){
        e.preventDefault();
        if(e.target === this){
          pageEditor.iframe_area_dragleaveHandler(this, e);
        }
      }).on('dragover', function(e){
        e.preventDefault();
      }).on('dragenter', function(e){
        e.preventDefault();
        if(e.target === this){
          pageEditor.iframe_area_dragenterHandler(this, e);
        }
      });
    });

    // Plugin Handler
    pageEditor.pageEditorPlugins.each(function(){
      pageEditor.addPluginHandler($(this));
    });
  },

  // ----------------------
  //  Plugin Handler
  // ----------------------
  iframe_plugin_editHandler(){
    // todo: plugin edit handler
    var plugin = $(this).parent().parent();
    var pluginId = plugin.data('id');
    var pluginName = plugin.data('plugin');
    var settings = pageEditor.getPluginSettings(plugin);
    pageEditor.page_settings(pluginName, pluginId, function(success, settings){
      if(success){
        pageEditor.addPluginPreview(pluginName, pluginId, settings, plugin, function(){
          plugin.remove();
          pageEditor.page_plugins();
        });
      }else{
        pageEditor.page_plugins();
      }
    }, settings);
  },

  iframe_plugin_deleteHandler(){
    // todo: nicer Confirm..
    if(confirm("Sure you want to delete?")){
      pageEditor.deletePlugin($(this).parent().parent());
    }
  },

  iframe_plugin_dragoverHandler(plugin, e){
    if(!pageEditor.isDropMarker()) pageEditor.dropMarker(plugin);
  },

  iframe_plugin_dragenterHandler(plugin, e){
    pageEditor.dropMarker(plugin);
  },

  // ----------------------
  //  Iframe "html" handler
  // ----------------------
  iframe_dropHandler(e) {
    pageEditor.removeDropMarker();
    var target = pageEditor.dropTarget;
    pageEditor.dropTarget = null;
    var pluginName = pageEditor.lastDraggedPlugin.data('name');

    // Show Settings Page and wait for Callback
    pageEditor.page_settings(pluginName,null,function(success, settings){
      // console.log("Add Plugin Settings:",settings);
      //  If success add the Preview Plugin, if not just back to plugin page
      if(success){
        pageEditor.addPluginPreview(pluginName, "", settings, target, function(success, errormsg){
          console.log("Add Plugin Success:" + success);
          console.log("Add Plugin Error:" + errormsg);
          pageEditor.page_plugins();
        });
      }else{
        pageEditor.page_plugins();
      }
    });
  },

  iframe_dragleaveHandler(plugin, e) {
    pageEditor.removeDropMarker();
  },

  // ----------------------
  //  Area handler
  // ----------------------
  iframe_area_dragenterHandler(area, e) {
    $(area).addClass('dragOver');
    pageEditor.dropMarker(area, true, true);
  },

  iframe_area_dragleaveHandler(area, e) {
    $(area).removeClass('dragOver');
    pageEditor.removeDropMarker();
    pageEditor.dropTarget = null;
  },


  //  ============================================
  //  PLUGIN FUNCTIONS
  //  ============================================
  getPluginSettings(plugin){
    return $(plugin).data('settings');
  },

  getPluginArea(plugin){
    return $(plugin).parent().data('name');
  },

  getSettingsValue(settings, key){
    var returnValue = null;
    if(Array.isArray(settings)){
      settings.forEach(function(element, index){
        if(element.settingkey === key){
          returnValue = element.settingvalue;
          // there is no break option, wtf !??
        }
      });
    }
    return returnValue;
  },

  addPluginHandler(plugin){
    plugin.find('.oxymora-plugin-edit').on('click', pageEditor.iframe_plugin_editHandler);
    plugin.find('.oxymora-plugin-delete').on('click', pageEditor.iframe_plugin_deleteHandler);
    plugin.on('dragover', function(e){
      e.preventDefault();
      pageEditor.iframe_plugin_dragoverHandler(this, e);
    }).on('dragenter', function(e){
      e.preventDefault();
      pageEditor.iframe_plugin_dragenterHandler(plugin, e);
    });
  },

  addPluginPreview(plugin, id, settings, target, callback){
    var data = {
      "id": id,
      "plugin": plugin,
      "settings": settings
    };
    $.ajax({
      dataType: "json",
      method: "POST",
      url: 'php/ajax_pageEditor.php?a=renderPluginPreview',
      data: data,
      success: function(data){
        var plugin = $(data.data);
        pageEditor.addPluginHandler(plugin);
        if(target.hasClass('oxymora-area')){
          target.prepend(plugin);
          callback(true, null);
        }else if(target.hasClass('oxymora-plugin')){
          plugin.insertAfter(target);
          callback(true, null);
        }else{
          callback(false, "Invalid Target!");
        }

      },
      error: function(){
        callback(false, null);
      }
    });
  },

  dropMarker(element, prepend, area = false){
    pageEditor.removeDropMarker(); // WHY DO U FUCK ME?
    pageEditor.dropTarget = $(element);
    pageEditor.dropIsActive = true;
    let display = (area) ? ' style="display:block;"' : ' style="display:block;"';
    let html = "<div"+display+" class='oxymora-drop-marker'>insert here</div>";
    if(prepend != null && prepend != false){
      pageEditor.dropTarget.prepend(html);
    }else{
      pageEditor.dropTarget.append(html);
    }
  },

  isDropMarker(){
    return pageEditor.dropIsActive;
  },

  removeDropMarker(){
    pageEditor.pageEditorPreview.contents().find('.oxymora-drop-marker').remove();
    pageEditor.dropIsActive = false;
  },

  deletePlugin(plugin){
    plugin[0].dataset.deleted = true;
    plugin.css('display', 'none');
  },


  //  ============================================
  //  FUNCTIONS
  //  ============================================

  getUrl(){
    return $("#pageEditorPreview").data('url');
  }

}