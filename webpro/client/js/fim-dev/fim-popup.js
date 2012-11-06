/*********************************************************
************************ START **************************
************** Repeat-Action Popup Methods **************
*********************************************************/

popup = {
  /*** START Login ***/

  login : function() {
    dia.full({
      content : window.templates.login,
      title : 'Login',
      id : 'loginDialogue',
      width : 600,
      oF : function() {
        // The following is a rather complicated hack that fixes a huge issue with how the login box first displays. It's stupid, but... yeah.
        manualHeight = ($(window).innerHeight() - 600) / 2;
        if (manualHeight < 0) manualHeight = 0;

        manualWidth = ($(window).innerWidth() - 600) / 2;
        if (manualWidth < 0) manualWidth = 0;

        $('#loginDialogue').parent().css('top', manualHeight);
        $('#loginDialogue').parent().css('left', manualWidth);
        $('#loginDialogue').parent().css('position', 'absolute');
        $('body').scrollTop();


        // Login Form Processing
        $("#loginForm").submit(function() {
          var userName = $('#loginForm > #userName').val(),
            password = $('#loginForm > #password').val(),
            rememberMe = $('#loginForm > #rememberme').is('checked');

          standard.login({
            userName : userName, password : password,
            showMessage : true, rememberMe : rememberMe
          });

          return false; // Don't submit the form.
        });
      },
      cF : function() {
        if (!userId) {
          standard.login({
            start : function() {
              $('<div class="ui-widget-overlay" id="loginWaitOverlay"></div>').appendTo('body').width($(document).width()).height($(document).height());
              $('<img src="images/ajax-loader.gif" id="loginWaitThrobber" />').appendTo('body').css('position', 'absolute').offset({ left : (($(window).width() - 220) / 2), top : (($(window).height() - 19) / 2)});
            },
            finish : function() {
              $('#loginWaitOverlay, #loginWaitThrobber').empty().remove();
            }
          });
        }

        return false;
      }
    });

    return false;
  },

  /*** END Login ***/




  /*** START Room Select ***/

  selectRoom : function() {
    dia.full({
      content : '<table class="center"><thead><tr><th style="width: 20%;">Name</th><th style="width: 60%;">Topic</th><th style="width: 20%;">Actions</th></tr></thead><tbody>' + roomTableHtml + '</tbody></table>',
      title : 'Room List',
      id : 'roomListDialogue',
      width: 1000,
      oF : function() {
        $('button.editRoomMulti, input[type=checkbox].favRoomMulti, button.archiveMulti, button.deleteRoomMulti').unbind('click'); // Prevent the below from being binded multiple times.


        $('button.editRoomMulti').button({icons : {primary : 'ui-icon-gear'}}).bind('click', function() {
          popup.editRoom($(this).attr('data-roomId'));
        });

        $('input[type=checkbox].favRoomMulti').button({icons : {primary : 'ui-icon-star'}, text : false}).bind('change', function() {
          if ($(this).is(':checked')) { standard.favRoom($(this).attr('data-roomId')); }
          else { standard.unfavRoom($(this).attr('data-roomId')); }
        });

        $('button.archiveMulti').button({icons : {primary : 'ui-icon-note'}}).bind('click', function() {
          popup.archive({roomId : $(this).attr('data-roomId')});
        });

        $('button.deleteRoomMulti').button({icons : {primary : 'ui-icon-trash'}}).bind('click', function() {
          standard.deleteRoom($(this).attr('data-roomId'));
        });
      }
    });

    return false;
  },

  /*** END Room List ***/




  /*** START Insert Docs ***/

  insertDoc : function(preselect) {
    var fileContent,
      selectTab;

    switch (preselect) {
      case 'video': selectTab = 2; break;
      case 'image': selectTab = 1; break;
      case 'link': default: selectTab = 0; break;
    }

    dia.full({
      content : window.templates.insertDoc,
      id : 'insertDoc',
      width: 600,
      tabs : true,
      oF : function() {
        var fileName,
          fileSize;

        $('#imageUploadSubmitButton').attr('disabled', 'disabled').button({ disabled: true });


        if (typeof FileReader !== 'function') {
          $('#uploadFileForm').html('Your device does not support file uploads.<br /><br />');
        }
        else {
          $('#fileUpload, #urlUpload').unbind('change'); // Prevent duplicate binds.
          $('#uploadFileForm, #uploadUrlForm, #linkForm. #uploadYoutubeForm').unbind('submit');


          /* Parental Controls */
          if (!serverSettings.parentalControls.parentalEnabled) { // Hide if Subsystem is Disabled
            $('#insertDocParentalAge, #insertDocParentalFlags').remove();
          }
          else {
            for (i in serverSettings.parentalControls.parentalAges) {
              $('#parentalAge').append('<option value="' + serverSettings.parentalControls.parentalAges[i] + '">' + window.phrases.parentalAges[serverSettings.parentalControls.parentalAges[i]] + '</option>');
            }
            for (i in serverSettings.parentalControls.parentalFlags) {
              $('#parentalFlagsList').append('<label>' +  window.phrases.parentalFlags[serverSettings.parentalControls.parentalFlags[i]] + '<input type="checkbox" value="true" name="flag' + serverSettings.parentalControls.parentalFlags[i] + '" data-cat="parentalFlag" data-name="' + serverSettings.parentalControls.parentalFlags[i] + '" /></label> ');
            }
          }


          /* Previewer for Files */
          $('#fileUpload').bind('change', function() {
            console.log('FileReader triggered.');

            var reader = new FileReader(),
              reader2 = new FileReader();

            files = this.files;

            if (files.length === 0) {
              dia.error('No files selected!');
            }
            else if (files.length > 1) {
              dia.error('Too many files selected!');
            }
            else {
              console.log('FileReader started.');

              fileName = files[0].name,
                fileSize = files[0].size;

              var fileParts = fileName.split('.');
              var filePartsLast = fileParts[fileParts.length - 1];

              if (!filePartsLast in uploadFileTypes) {
                $('#preview').html('The specified file type can not be uploaded.');
              }
              else if (fileSize > uploadFileTypes[filePartsLast].maxSize) {
                $('#preview').html('The specified file type must not be larger than ' + uploadFileTypes[filePartsLast].maxSize + ' bytes');
              }
              else {
                reader.readAsBinaryString(files[0]);
                reader.onloadend = function() {
                  fileContent = window.btoa(reader.result);
                };

                reader2.readAsDataURL(files[0]);
                reader2.onloadend = function() {
                  switch (uploadFileTypes[filePartsLast].container) {
                    case 'image': $('#uploadFileFormPreview').html('<img src="' + reader2.result + '" style="max-height: 200px; max-width: 200px;" />'); break;
                    case 'video': $('#uploadFileFormPreview').html('No Preview Available'); break;
                    case 'audio': $('#uploadFileFormPreview').html('No Preview Available'); break;
                    case 'text': $('#uploadFileFormPreview').html('No Preview Available'); break;
                    case 'html': $('#uploadFileFormPreview').html('No Preview Available'); break;
                    case 'archive': $('#uploadFileFormPreview').html('No Preview Available'); break;
                    case 'other': $('#uploadFileFormPreview').html('No Preview Available'); break;
                  }
                };
              }

              $('#imageUploadSubmitButton').removeAttr('disabled').button({ disabled: false });
            }
          });


          /* Previewer for URLs */
          $('#urlUpload').bind('change', function() {
            fileContent = $('#urlUpload').val();
            if (fileContent && fileContent !== 'http://') {
              fileContainer = '<img src="' + fileContent + '" alt="" style="max-width: 200px; max-height: 250px; height: auto;" />';

              $('#uploadUrlFormPreview').html(fileContainer);
            }
          });


          /* Submit */
          $('#uploadFileForm').bind('submit', function() {
            parentalAge = $('#parentalAge option:selected').val(),
            parentalFlags = [];

            $('input[data-cat=parentalFlag]:checked').each(function(a, b) {
              parentalFlags.push($(b).attr('data-name'));
            });

            $.ajax({
              url : directory + 'api/editFile.php',
              type : 'POST',
              data : 'action=create&dataEncode=base64&uploadMethod=raw&autoInsert=true&roomId=' + roomId + '&fileName=' + fileName + '&parentalAge=' + parentalAge + '&parentalFlags=' + parentalFlags.join(',') + '&fileData=' + fim_eURL(fileContent) + '&fim3_sessionHash=' + sessionHash + '&fim3_userId=' + userId + '&fim3_format=json',
              cache : false,
              success : function(json) {
                var errStr = json.editFile.errStr,
                  errDesc = json.editFile.errDesc;

                if (errStr) {
                  dia.error(errDesc);
                }
              },
              error : function() {
                dia.error('The file failed to upload.');
              }
            });

            $('#insertDoc').dialog('close');

            return false;
          });
        }

        $('#uploadUrlForm').bind('submit', function() {
          var linkImage = $('#urlUpload').val();
          if (linkImage) { standard.sendMessage(linkImage, 0, 'image'); }
          $('#insertDoc').dialog('close');

          return false;
        });

        $('#linkForm').bind('submit', function() {
          var linkUrl = $('#linkUrl').val(),
            linkMail = $('#linkEmail').val();

          if (linkUrl.length === 0 && linkMail.length === 0) { dia.error('No Link Was Specified'); } // No value for either.
          else if (linkUrl.length > 0) { standard.sendMessage(linkUrl, 0, 'url'); } // Link specified for URL.
          else if (linkMail.length > 0) { standard.sendMessage(linkMail, 0, 'email'); } // Link specified for mail, not URL.
          else { dia.error('Logic Error'); } // Eh, why not?

          $('#insertDoc').dialog('close');

          return false;
        });

        $('#uploadYoutubeForm').bind('submit', function() {
          linkVideo = $('#youtubeUpload');

          if (linkVideo.search(/^http\:\/\/(www\.|)youtube\.com\/(.*?)?v=(.+?)(&|)(.*?)$/) === 0) { dia.error('No Video Specified'); } // Bad format
          else { standard.sendMessage(linkVideo, 0, 'source'); }

          $('#insertDoc').dialog('close');

          return false;
        });

        return false;
      },
      selectTab : selectTab
    });

    return false;
  },

  /*** END Insert Docs ***/




  /*** START Stats ***/

  viewStats : function() {
    var statsHtml = {}, // Object
      statsHtml2 = '',
      roomHtml = '',
      number = 10,
      i = 1;

    for (i = 1; i <= number; i += 1) {
      statsHtml[i] = '';
    }

    $.ajax({
      url: directory + 'api/getStats.php?rooms=' + roomId + '&number=' + number + '&fim3_sessionHash=' + sessionHash + '&fim3_userId=' + userId + '&fim3_format=json',
      timeout: 5000,
      type: 'GET',
      cache: false,
      success: function(json) {
        for (i in json.getStats.roomStats) {
          var roomName = json.getStats.roomStats[i].roomData.roomName,
            roomId = json.getStats.roomStats[i].roomData.roomId;

          for (j in json.getStats.roomStats[i].users) {
            var userName = json.getStats.roomStats[i].users[j].userData.userName,
              userId = json.getStats.roomStats[i].users[j].userData.userId,
              startTag = json.getStats.roomStats[i].users[j].userData.startTag,
              endTag = json.getStats.roomStats[i].users[j].userData.endTag,
              position = json.getStats.roomStats[i].users[j].position,
              messageCount = json.getStats.roomStats[i].users[j].messageCount;

            statsHtml[position] += '<td><span class="userName userNameTable" data-userId="' + userId + '">' + startTag + userName + endTag + '</span> (' + messageCount + ')</td>';
          };


          roomHtml += '<th>' + roomName + '</th>';

        }

        for (i = 1; i <= number; i += 1) {
          statsHtml2 += '<tr><th>' + i + '</th>' + statsHtml[i] + '</tr>';
        }

        dia.full({
          content : '<table class="center"><thead><tr><th>Position</th>' + roomHtml + '</tr></thead><tbody>' + statsHtml2 + '</tbody></table>',
          title : 'Room Stats',
          id : 'roomStatsDialogue',
          width : 600
        });

        return false;
      },
      error: function() {
        dia.error('Failed to obtain stats. The action will be cancelled'); // TODO: Handle Gracefully

        return false;
      }
    });

    return false;
  },

  /*** END Stats ***/




  /*** START User Settings ***/

  userSettings : function() { /* TODO: Handle reset properly, and refresh the entire application when settings are changed. It used to make some sense not to, but not any more. */
    dia.full({
      content : window.templates.userSettingsForm,
      id : 'changeSettingsDialogue',
      tabs : true,
      width : 1000,
      cF : function() {
        $('.colorpicker').empty().remove();

        return false;
      },
      oF : function() {
        var defaultColour = false,
          defaultHighlight = false,
          defaultFontface = false,
          idMap = {
            disableFormatting : 16, disableImage : 32, disableVideos : 64, reversePostOrder : 1024,
            showAvatars : 2048, audioDing : 8192, disableFx : 262144, disableRightClick : 1048576,
            usTime : 16777216, twelveHourTime : 33554432, webkitNotifications : 536870912
          };

        $.get(directory + 'api/getUsers.php?users=' + userId + '&fim3_sessionHash=' + sessionHash + '&fim3_userId=' + userId + '&fim3_format=json', function(json) {
          active = json.getUsers.users;

          for (i in active) {
            defaultColour = active[i].defaultFormatting.color;
            defaultHighlight = active[i].defaultFormatting.highlight;
            defaultFontface = active[i].defaultFormatting.fontface;

            var defaultGeneral = active[i].defaultFormatting.general,
              ignoreList = active[i].ignoreList,
              watchRooms = active[i].watchRooms,
              options = active[i].options,
              defaultRoom = active[i].defaultRoom,
              defaultHighlightHashPre = [],
              defaultHighlightHash = {r:0, g:0, b:0},
              defaultColourHashPre = [],
              defaultColourHash = {r:0, g:0, b:0},
              parentalAge = active[i].parentalAge,
              parentalFlags = active[i].parentalFlags;

            /* Update Default Forum Values Based on Server Settings */
            // User Profile
            if (active[i].profile) {
              $('#profile').val(active[i].profile);
            }

            // Default Formatting -- Bold
            if (defaultGeneral & 256) {
              $('#fontPreview').css('font-weight', 'bold');
              $('#defaultBold').attr('checked', 'checked');
            }
            $('#defaultBold').change(function() {
              if ($('#defaultBold').is(':checked')) $('#fontPreview').css('font-weight', 'bold');
              else $('#fontPreview').css('font-weight', 'normal');
            });

            // Default Formatting -- Italics
            if (defaultGeneral & 512) {
              $('#fontPreview').css('font-style', 'italic');
              $('#defaultItalics').attr('checked', 'checked');
            }
            $('#defaultItalics').change(function() {
              if ($('#defaultItalics').is(':checked')) $('#fontPreview').css('font-style', 'italic');
              else $('#fontPreview').css('font-style', 'normal');
            });

            // Default Formatting -- Font Colour
            if (defaultColour) {
              $('#fontPreview').css('color', 'rgb(' + defaultColour + ')');
              $('#defaultColour').css('background-color', 'rgb(' + defaultColour + ')');

              defaultColourHashPre = defaultColour.split(',');
              defaultColourHash = {r : defaultColourHashPre[0], g : defaultColourHashPre[1], b : defaultColourHashPre[2] }
            }

            // Default Formatting -- Highlight Colour
            if (defaultHighlight) {
              $('#fontPreview').css('background-color', 'rgb(' + defaultHighlight + ')');
              $('#defaultHighlight').css('background-color', 'rgb(' + defaultHighlight + ')');

              defaultHighlightHashPre = defaultHighlight.split(',');
              defaultHighlightHash = {r : defaultHighlightHashPre[0], g : defaultHighlightHashPre[1], b : defaultHighlightHashPre[2] }
            }

            // Default Formatting -- Fontface
            if (defaultFontface) {
              $('#defaultFace > option[value="' + defaultFontface + '"]').attr('selected', 'selected');
            }
            $('#defaultFace').change(function() {
              $('#fontPreview').css('fontFamily', $('#defaultFace > option:selected').attr('data-font'));
            });

            // Colour Chooser -- Colour
            $('#defaultColour').ColorPicker({
              color: defaultColourHash,
              onShow: function (colpkr) { $(colpkr).fadeIn(500); }, // Fadein
              onHide: function (colpkr) { $(colpkr).fadeOut(500); }, // Fadeout
              onChange: function(hsb, hex, rgb) {
                defaultColour = rgb['r'] + ',' + rgb['g'] + ',' + rgb['b'];

                $('#defaultColour').css('background-color', 'rgb(' + defaultColour + ')');
                $('#fontPreview').css('color', 'rgb(' + defaultColour + ')');
              }
            });

            // Colour Chooser -- Highlight
            $('#defaultHighlight').ColorPicker({
              color: defaultHighlightHash,
              onShow: function (colpkr) { $(colpkr).fadeIn(500); }, // Fadein
              onHide: function (colpkr) { $(colpkr).fadeOut(500); }, // Fadeout
              onChange: function(hsb, hex, rgb) {
                defaultHighlight = rgb['r'] + ',' + rgb['g'] + ',' + rgb['b'];

                $('#defaultHighlight').css('background-color', 'rgb(' + defaultHighlight + ')');
                $('#fontPreview').css('background-color', 'rgb(' + defaultHighlight + ')');
              }
            });

            // Default Room Value
            $('#defaultRoom').val(roomIdRef[defaultRoom].roomName);

            // Populate Existing Entries for Lists
            autoEntry.showEntries('ignoreList', ignoreList);
            autoEntry.showEntries('watchRooms', watchRooms);

            // Parental Control Flags
            for (i in parentalFlags) {
              $('input[data-cat=parentalFlag][data-name=' + parentalFlags[i] + ']').attr('checked', true);
            }
            $('select#parentalAge option[value=' + parentalAge + ']').attr('selected', 'selected');

            return false;
          }
        });


        /* Update Default Form Values to Client Settings */
        // Boolean Checkboxes
        if (settings.reversePostOrder) $('#reversePostOrder').attr('checked', 'checked');
        if (settings.showAvatars) $('#showAvatars').attr('checked', 'checked');
        if (settings.audioDing) $('#audioDing').attr('checked', 'checked');
        if (settings.disableFx) $('#disableFx').attr('checked', 'checked');
        if (settings.disableFormatting) $('#disableFormatting').attr('checked', 'checked');
        if (settings.disableVideo) $('#disableVideo').attr('checked', 'checked');
        if (settings.disableImage) $('#disableImage').attr('checked', 'checked');
        if (settings.disableRightClick) $('#disableRightClick').attr('checked', 'checked');
        if (settings.webkitNotifications) $('#webkitNotifications').attr('checked', 'checked');
        if (settings.twelveHourTime) $('#twelveHourFormat').attr('checked', 'checked');
        if (settings.usTime) $('#usTime').attr('checked', 'checked');

        // Volume
        if (snd.volume) $('#audioVolume').attr('value', snd.volume * 100);

        // Select Boxes
        if (theme) $('#theme > option[value="' + theme + '"]').attr('selected', 'selected');
        if (fontsize) $('#fontsize > option[value="' + fontsize + '"]').attr('selected', 'selected');

        // Only Show the Profile Setting if Using Vanilla Logins
        if (serverSettings.branding.forumType !== 'vanilla') $('#settings5profile').hide(0);

        // Autocomplete Rooms and Users
        $("#defaultRoom").autocomplete({ source: roomList });
        $("#watchRoomsBridge").autocomplete({ source: roomList });
        $("#ignoreListBridge").autocomplete({ source: userList });

        // Populate Fontface Checkbox
        for (i in window.serverSettings.formatting.fonts) {
          $('#defaultFace').append('<option value="' + i + '" style="' + window.serverSettings.formatting.fonts[i] + '" data-font="' + window.serverSettings.formatting.fonts[i] + '">' + i + '</option>')
        }

        // Parental Controls
        if (!serverSettings.parentalControls.parentalEnabled) { // Hide if Subsystem is Disabled
          $('a[href="#settings5"]').parent().remove();
        }
        else {
          for (i in serverSettings.parentalControls.parentalAges) {
            $('#parentalAge').append('<option value="' + serverSettings.parentalControls.parentalAges[i] + '">' + window.phrases.parentalAges[serverSettings.parentalControls.parentalAges[i]] + '</option>');
          }
          for (i in serverSettings.parentalControls.parentalFlags) {
            $('#parentalFlagsList').append('<label>' +  window.phrases.parentalFlags[serverSettings.parentalControls.parentalFlags[i]] + '<input type="checkbox" value="true" name="flag' + serverSettings.parentalControls.parentalFlags[i] + '" data-cat="parentalFlag" data-name="' + serverSettings.parentalControls.parentalFlags[i] + '" /></label> ');
          }
        }


        /* Actions onChange */
        // Theme -- Update onChange
        $('#theme').change(function() {
          $('#stylesjQ').attr('href', 'client/css/' + this.value + '/jquery-ui-1.8.16.custom.css');
          $('#stylesFIM').attr('href', 'client/css/' + this.value + '/fim.css');

          $.cookie('webpro_theme', this.value, { expires : 14 });
          theme = this.value;

          return false;
        });

        // Theme Fontsize -- Update onChange
        $('#fontsize').change(function() {
          $('body').css('font-size',this.value + 'em');

          $.cookie('webpro_fontsize', this.value, { expires : 14 });
          fontsize = this.value;

          return false;
        });

        // Volume -- Update onChange
        $('#audioVolume').change(function() {
          $.cookie('webpro_audioVolume', this.value, { expires : 14 });
          snd.volume = this.value / 100;

          return false;
        });

        // Various Settings -- Update onChange, Refresh Posts
        $('#showAvatars, #reversePostOrder, #disableFormatting, #disableVideo, #disableImage').change(function() {
          var localId = $(this).attr('id');

          if ($(this).is(':checked') && !settings[localId]) {
            settings[localId] = true;
            $('#messageList').html('');
            $.cookie('webpro_settings', Number($.cookie('webpro_settings')) + idMap[localId], { expires : 14 });
          }
          else if (!$(this).is(':checked') && settings[localId]) {
            settings[localId] = false;
            $('#messageList').html('');
            $.cookie('webpro_settings', Number($.cookie('webpro_settings')) - idMap[localId], { expires : 14 });
          }

          requestSettings.firstRequest = true;
          requestSettings.lastMessage = 0;
          messageIndex = [];
        });

        // Various Settings -- Update onChange
        $('#audioDing, #disableFx, #webkitNotifications, #disableRightClick').change(function() {
          var localId = $(this).attr('id');

          if ($(this).is(':checked') && !settings[localId]) {
            settings[localId] = true;
            $.cookie('webpro_settings', Number($.cookie('webpro_settings')) + idMap[localId], { expires : 14 });

            if (localId === 'disableFx') { jQuery.fx.off = true; } // Disable jQuery Effects
            if (localId === 'webkitNotifications' && 'webkitNotifications' in window) { window.webkitNotifications.requestPermission(); } // Ask client permission for webkit notifications
          }
          else if (!$(this).is(':checked') && settings[localId]) {
            settings[localId] = false;
            $.cookie('webpro_settings', Number($.cookie('webpro_settings')) - idMap[localId], { expires : 14 });

            if (localId === 'disableFx') { jQuery.fSystemx.off = false; } // Reenable jQuery Effects
          }
        });


        /* Submit Processer */
        $("#changeSettingsForm").submit(function() {
          var watchRooms = $('#watchRooms').val(),
            defaultRoom = $('#defaultRoom').val(),
            ignoreList = $('#ignoreList').val(),
            profile = $('#profile').val(),
            defaultRoomId = (defaultRoom ? roomRef[defaultRoom] : 0),
            fontId = $('#defaultFace option:selected').val(),
            defaultFormatting = ($('#defaultBold').is(':checked') ? 256 : 0) + ($('#defaultItalics').is(':checked') ? 512 : 0),
            parentalAge = $('#parentalAge option:selected').val(),
            parentalFlags = [];

          $('input[data-cat=parentalFlag]:checked').each(function(a, b) {
            parentalFlags.push($(b).attr('data-name'));
          });

          $.post(directory + 'api/editUserOptions.php', 'defaultFormatting=' + defaultFormatting + '&defaultColor=' + defaultColour + '&defaultHighlight=' + defaultHighlight + '&defaultRoomId=' + defaultRoomId + '&watchRooms=' + watchRooms + '&ignoreList=' + ignoreList + '&profile=' + profile + '&defaultFontface=' + fontId + '&parentalAge=' + parentalAge + '&parentalFlags=' + parentalFlags.join(',') + '&fim3_sessionHash=' + sessionHash + '&fim3_userId=' + userId + '&fim3_format=json', function(json) {
            dia.info('Your settings may or may not have been updated.');
          }); // Send the form data via AJAX.

          $("#changeSettingsDialogue").empty().remove(); // Housecleaning, needed if we want the colorpicker to work in another changesettings dialogue.
          $(".colorpicker").empty().remove(); // Housecleaning, needed if we want the colorpicker to work in another changesettings dialogue.

          return false; // Don't submit the form.
        });

        return false;
      }
    });

    return false;
  },

  /*** END User Settings ***/






  /*** START View My Uploads ***/

  viewUploads : function() {
    dia.full({
      content : '<table align="center"><thead><tr><td>File</td><td>Size</td><td>Parental Info</td><td>Actions</td></tr></thead><tbody id="viewUploadsBody"></tbody></table>',
      width : 1200,
      title : 'View My Uploads',
      position : 'top',
      oF : function() {
        $.ajax({
          url: directory + 'api/getFiles.php?users=' + userId + '&fim3_sessionHash=' + sessionHash + '&fim3_userId=' + userId + '&fim3_format=json',
          type: 'GET',
          timeout: 2400,
          cache: false,
          success: function(json) {
            active = json.getFiles.files;

            for (i in active) {
              var fileName = active[i].fileName,
                md5hash = active[i].md5hash,
                sha256hash = active[i].sha256hash,
                fileSizeFormatted = active[i].fileSizeFormatted,
                parentalAge = active[i].parentalAge,
                parentalFlags = active[i].parentalFlags,
                parentalFlagsFormatted = [];

                for (i in parentalFlags) { console.log(parentalFlags[i]);
                  parentalFlagsFormatted.push(window.phrases.parentalFlags[parentalFlags[i]]); // Yes, this is a very weird line.
                }

                $('#viewUploadsBody').append('<tr><td align="center"><img src="' + directory + 'file.php?sha256hash=' + sha256hash + '&fim3_sessionHash=' + sessionHash + '&fim3_userId=' + userId + '&fim3_format=json" style="max-width: 200px; max-height: 200px;" /><br />' + fileName + '</td><td align="center">' + fileSizeFormatted + '</td><td align="center">' + window.phrases.parentalAges[parentalAge] + '<br />' + parentalFlagsFormatted.join(', ') + '</td><td align="center"><button onclick="standard.changeAvatar(\'' + sha256hash + '\')">Set to Avatar</button></td></tr>');
            }

            return false;
          },
          error: function() {
            dia.error('Could not obtain uploads. The action will be cancelled.'); // TODO: Handle Gracefully
          }
        });

        return false;
      }
    });
  },

  /*** END View My Uploads ***/






  /*** START Create Room ***/

  editRoom : function(roomIdLocal) {
    if (roomIdLocal) var action = 'edit';
    else var action = 'create';

    dia.full({
      content : window.templates.editRoomForm,
      id : 'editRoomDialogue',
      width : 1000,
      tabs : true,
      oF : function() {
        if (roomIdLocal) {
          $.ajax({
            url: directory + 'api/getRooms.php?rooms=' + roomIdLocal + '&fim3_sessionHash=' + sessionHash + '&fim3_userId=' + userId + '&fim3_format=json',
            type: 'GET',
            timeout: 2400,
            cache: false,
            success: function(json) {
              for (var i in json.getRooms.rooms) {
                var data = '',
                  roomName = json.getRooms.rooms[i].roomName,
                  roomId = json.getRooms.rooms[i].roomId,
                  allowedUsers = json.getRooms.rooms[i].allowedUsers,
                  allowedGroups = json.getRooms.rooms[i].allowedGroups,
                  defaultPermissions = json.getRooms.rooms[i].defaultPermissions,
                  parentalAge = json.getRooms.rooms[i].parentalAge,
                  parentalFlags = json.getRooms.rooms[i].parentalFlags,
                  allowedUsersArray = [],
                  moderatorsArray = [],
                  allowedGroupsArray = [];

                  for (var j in allowedUsers) {
                    if (allowedUsers[j] & 15 === 15) { moderatorsArray.push(j); } // Are all bits up to 8 present?
                    if (allowedUsers[j] & 7 === 7) { allowedUsersArray.push(j); } // Are the 1, 2, and 4 bits all present?
                  }

                  console.log(parentalFlags);
                  for (i in parentalFlags) {
                    $('input[data-cat=parentalFlag][data-name=' + parentalFlags[i] + ']').attr('checked', true);
                  }
                  $('select#parentalAge option[value=' + parentalAge + ']').attr('selected', 'selected');

                break;
              }

              $('#name').val(roomName); // Current Room Name

              /* Prepopulate */
              // User Autocomplete
              if (allowedUsersArray.length > 0) autoEntry.showEntries('allowedUsers', allowedUsersArray);
              if (moderatorsArray.length > 0) autoEntry.showEntries('moderators', moderatorsArray);
              if (allowedGroupsArray.length > 0) autoEntry.showEntries('allowedGroups', allowedGroupsArray);

              if (defaultPermissions == 7) { // Are All Users Allowed Presently?
                $('#allowAllUsers').attr('checked', true);
                $('#allowedUsersBridge').attr('disabled', 'disabled');
                $('#allowedGroupsBridge').attr('disabled', 'disabled');
                $('#allowedUsersBridge').next().attr('disabled', 'disabled');
                $('#allowedGroupsBridge').next().attr('disabled', 'disabled');
              }

              return false;
            },
            error: function() {
              dia.error('Failed to obtain current room settings from server. The action will be cancelled.'); // TODO: Handle Gracefully

              return false;
            }
          });
        }


        /* Censor Lists */
        $.ajax({
          url: directory + 'api/getCensorLists.php?rooms=' + roomIdLocal + '&fim3_sessionHash=' + sessionHash + '&fim3_userId=' + userId + '&fim3_format=json',
          type: 'GET',
          timeout: 2400,
          cache: false,
          success: function(json) {
            for (var i in json.getCensorLists.lists) {
              var listId = json.getCensorLists.lists[i].listId,
                listName = json.getCensorLists.lists[i].listName,
                listType = json.getCensorLists.lists[i].listType,
                listOptions = json.getCensorLists.lists[i].listOptions;

              for (j in json.getCensorLists.lists[i].active) {
                var listStatus = json.getCensorLists.lists[i].active[j].status;
              }

              $('#censorLists').append('<label><input type="checkbox" name="list' + listId + '" data-listId="' + listId + '" data-checkType="list" value="true" ' + (listOptions & 2 ? '' : ' disabled="disabled"') + (listStatus === 'block' ? ' checked="checked"' : '') + ' />' + listName + '</label>');
            }

            return false;
          },
          error: function() {
            dia.error('Failed to obtain current censor list settings from server. The action will be cancelled.'); // TODO: Handle Gracefully

            return false;
          }
        });


        /* Autocomplete Users and Groups */
        $("#moderatorsBridge").autocomplete({ source: userList });
        $("#allowedUsersBridge").autocomplete({ source: userList });
        $("#allowedGroupsBridge").autocomplete({ source: groupList });

        $('#allowAllUsers').change(function() {
          if ($(this).is(':checked')) {
            $('#allowedUsersBridge').attr('disabled', 'disabled');
            $('#allowedGroupsBridge').attr('disabled', 'disabled');
            $('#allowedUsersBridge').next().attr('disabled', 'disabled');
            $('#allowedGroupsBridge').next().attr('disabled', 'disabled');
          }
          else {
            $('#allowedUsersBridge').removeAttr('disabled');
            $('#allowedGroupsBridge').removeAttr('disabled');
            $('#allowedUsersBridge').next().removeAttr('disabled');
            $('#allowedGroupsBridge').next().removeAttr('disabled');
          }
        });


        /* Parental Controls */
        if (!serverSettings.parentalControls.parentalEnabled) { // Hide if Subsystem is Disabled
          $('#editRoom1ParentalAge, #editRoom1ParentalFlags').remove();
        }
        else {
          for (i in serverSettings.parentalControls.parentalAges) {
            $('#parentalAge').append('<option value="' + serverSettings.parentalControls.parentalAges[i] + '">' + window.phrases.parentalAges[serverSettings.parentalControls.parentalAges[i]] + '</option>');
          }
          for (i in serverSettings.parentalControls.parentalFlags) {
            $('#parentalFlagsList').append('<label>' +  window.phrases.parentalFlags[serverSettings.parentalControls.parentalFlags[i]] + '<input type="checkbox" value="true" name="flag' + serverSettings.parentalControls.parentalFlags[i] + '" data-cat="parentalFlag" data-name="' + serverSettings.parentalControls.parentalFlags[i] + '" /></label> ');
          }
        }


        /* Submit */
        $("#editRoomForm").submit(function() {
          var name = $('#name').val(),
            allowedUsers = $('#allowedUsers').val(),
            allowedGroups = $('#allowedGroups').val(),
            moderators = $('#moderators').val(),
            censor = [],
            parentalAge = $('#parentalAge option:selected').val(),
            parentalFlags = [];

          $('input[data-checkType="list"]').each(function() {
            censor.push($(this).attr('data-listId') + '=' + ($(this).is(':checked') ? 1 : 0));
          });

          $('input[data-cat=parentalFlag]:checked').each(function(a, b) {
            parentalFlags.push($(b).attr('data-name'));
          });

          censor = censor.join(',');console.log(censor); // TODO

          if (name.length > window.serverSettings.rooms.roomLengthMaximum) dia.error('The roomname is too long.');
          else if (name.length < window.serverSettings.rooms.roomLengthMinimum) dia.error('The roomname is too short.');
          else {
            $.post(directory + 'api/editRoom.php', 'action=' + action + '&roomId=' +  roomIdLocal + '&roomName=' + fim_eURL(name) + '&defaultPermissions=' + ($('#allowAllUsers').is(':checked') ? '7' : '0' + '&allowedUsers=' + allowedUsers + '&allowedGroups=' + allowedGroups) + '&moderators=' + moderators + '&parentalAge=' + parentalAge + '&parentalFlags=' + parentalFlags + '&fim3_sessionHash=' + sessionHash + '&fim3_userId=' + userId, function(json) {
              var errStr = json.editRoom.errStr,
                errDesc = json.editRoom.errDesc,
                createRoomId = json.editRoom.response.insertId;

              if (errStr) {
                dia.error('An error has occured: ' + errDesc);
              }
              else {
                dia.full({
                  content : 'The room has been created at the following URL:<br /><br /><form action="' + currentLocation + '#room=' + createRoomId + '" method="post"><input type="text" style="width: 300px;" value="' + currentLocation + '#room=' + createRoomId + '" name="url" /></form>',
                  title : 'Room Created!',
                  id : 'editRoomResultsDialogue',

                  width : 600,
                  buttons : {
                    Open : function() {
                      $('#editRoomResultsDialogue').dialog('close');
                      standard.changeRoom(createRoomId);

                      return false;
                    },
                    Okay : function() {
                      $('#editRoomResultsDialogue').dialog('close');

                      return false;
                    }
                  }
                });

                $("#editRoomDialogue").dialog('close');
              }
            }); // Send the form data via AJAX.
          }

          return false; // Don't submit the form.
        });

        return false;
      }
    });

    return false;
  },

  /*** END Create Room ***/




  /*** START Private Rooms ***/

  privateRoom : function() {
    dia.full({
      content : '<form action="index.php?action=privateRoom&phase=2" method="post" id="privateRoomForm"><label for="userName">Username</label>: <input type="text" name="userName" id="userName" /><br /><small><span style="margin-left: 10px;">The other user you would like to talk to.</span></small><br /><br />  <button type="submit">Go</button></form>',
      title : 'Enter Private Room',
      id : 'privateRoomDialogue',
      width : 1000,
      oF : function() {
        $('#userName').autocomplete({
          source: userList
        });

        $("#privateRoomForm").submit(function() {
          privateUserName = $("#privateRoomForm > #userName").val(); // Serialize the form data for AJAX.
          privateUserId = userRef[privateUserName];

          standard.privateRoom(privateUserId);

          return false; // Don't submit the form.
        });

        return false;
      }
    });

    return false;
  },

  /*** END Private Rooms ***/




  /*** START Online ***/

  online : function() {
    dia.full({
      content : '<table class="center"><thead><tr class="hrow"><th>User</th><th>Rooms</th></tr></thead><tbody id="onlineUsers"><tr><td colspan="2">Loading...</td></tr></tbody></table>',
      title : 'Active Users',
      id : 'onlineDialogue',
      position : 'top',
      width : 600,
      cF : function() {
        clearInterval(timers.t2);
      }
    });

    function updateOnline() {
      $.ajax({
        url: directory + 'api/getAllActiveUsers.php?fim3_sessionHash=' + sessionHash + '&fim3_userId=' + userId + '&fim3_format=json',
        type: 'GET',
        timeout: 2400,
        cache: false,
        success: function(json) {
          var data = '';

          active = json.getAllActiveUsers.users;

          for (i in active) {
            var userName = active[i].userData.userName,
              userId = active[i].userData.userId,
              startTag = active[i].userData.startTag,
              endTag = active[i].userData.endTag,
              roomData = [];

            for (j in active[i].rooms) {
              var roomId = active[i].rooms[j].roomId,
                roomName = active[i].rooms[j].roomName;
              roomData.push('<a href="#room=' + roomId + '">' + roomName + '</a>');
            }
            roomData = roomData.join(', ');

            data += '<tr><td>' + startTag + '<span class="userName" data-userId="' + userId + '">' + userName + '</span>' + endTag + '</td><td>' + roomData + '</td></tr>';
          }

          $('#onlineUsers').html(data);
          contextMenuParseUser('#onlineUsers');

          return false;
        },
        error: function() {
          $('#onlineUsers').html('Refresh Failed');
        }
      });

      return false;
    }

    timers.t2 = setInterval(updateOnline, 2500);

    return false;
  },

  /*** END Online ***/




  /*** START Kick Manager ***/

  manageKicks : function() {
    var kickHtml = '';

    $.ajax({
      url: directory + 'api/getKicks.php?rooms=' + roomId + '&fim3_sessionHash=' + sessionHash + '&fim3_userId=' + userId + '&fim3_format=json',
      timeout: 5000,
      type: 'GET',
      cache: false,
      success: function(json) {
        active = json.getKicks.kicks;

        for (i in active) {
          var kickerId = active[i].kickerData.userId,
            kickerName = active[i].kickerData.userName,
            kickerFormatStart = active[i].kickerData.userFormatStart,
            kickerFormatEnd = active[i].kickerData.userFormatEnd,
            userId = active[i].userData.userId,
            userName = active[i].userData.userName,
            userFormatStart = active[i].userData.userFormatStart,
            userFormatEnd = active[i].userData.userFormatEnd,
            length = active[i].length,
            set = date(active[i].set, true),
            expires = date(active[i].expires, true);

          kickHtml += '<tr><td>' + userFormatStart + '<span class="userName userNameTable" data-userId="' + userId + '">' + userName + '</span>' + userFormatEnd + '</td><td>' + kickerFormatStart + '<span class="userName userNameTable" data-userId="' + kickerId + '">' + kickerName + '</span>' + kickerFormatEnd + '</td><td>' + set + '</td><td>' + expires + '</td><td><button onclick="standard.unkick(' + userId + ', ' + roomId + ')">Unkick</button></td></tr>';
        }

        dia.full({
          content : '<table class="center"><thead><tr class="hrow"><th>User</th><th>Kicked By</th><th>Kicked On</th><th>Expires On</th><th>Actions</th></tr>  </thead><tbody id="kickedUsers">' + kickHtml + '</tbody></table>',
          title : 'Manage Kicked Users in This Room',
          width : 1000
        });

        return false;
      },
      error: function() {
        dia.error('The list of currently kicked users could not be obtained from the server. The action will be cancelled.'); // TODO: Handle Gracefully

        return false;
      }
    });

    return false;
  },

  /*** END Kick Manager ***/




  /*** START My Kicks ***/

  myKicks : function() {
    var kickHtml = '';

    $.ajax({
      url: directory + 'api/getKicks.php?users=' + userId + '&fim3_sessionHash=' + sessionHash + '&fim3_userId=' + userId + '&fim3_format=json',
      timeout: 5000,
      type: 'GET',
      cache: false,
      success: function(json) {
        active = json.getKicks.kicks;

        for (i in active) {
          var kickerId = active[i].kickerData.userId,
            kickerName = active[i].kickerData.userName,
            kickerFormatStart = active[i].kickerData.userFormatStart,
            kickerFormatEnd = active[i].kickerData.userFormatEnd,
            userId = active[i].userData.userId,
            userName = active[i].userData.userName,
            userFormatStart = active[i].userData.userFormatStart,
            userFormatEnd = active[i].userData.userFormatEnd,
            length = active[i].length,
            set = date(active[i].set, true),
            expires = date(active[i].expires, true);

          kickHtml += '<tr><td>' + userFormatStart + '<span class="userName userNameTable" data-userId="' + userId + '">' + userName + '</span>' + userFormatEnd + '</td><td>' + kickerFormatStart + '<span class="userName userNameTable" data-userId="' + kickerId + '">' + kickerName + '</span>' + kickerFormatEnd + '</td><td>' + set + '</td><td>' + expires + '</td></tr>';
        }

        dia.full({
          content : '<table class="center"><thead><tr class="hrow"><th>User</th><th>Kicked By</th><th>Kicked On</th><th>Expires On</th></tr>  </thead><tbody id="kickedUsers">' + kickHtml + '</tbody></table>',
          title : 'You Have Been Kicked From The Following Rooms',
          width : 1000
        });

        return false;
      },
      error: function() {
        dia.error('The list of currently kicked users could not be obtained from the server. The action will be cancelled.'); // TODO: Handle Gracefully

        return false;
      }
    });

    return false;
  },

  /*** END Kick Manager ***/




  /*** START Kick ***/

  kick : function() {
    dia.full({
      content : '<form action="#" id="kickUserForm" method="post">  <label for="userName">User</label>: <input type="text" name="userName" id="userName" /><br />  <label for="roomNameKick">Room</label>: <input type="text" id="roomNameKick" name="roomNameKick" /> <br />  <label for="time">Time</label>: <input type="text" name="time" id="time" style="width: 50px;" />  <select name="interval" id="interval">    <option value="1">Seconds</option>    <option value="60">Minutes</option>    <option value="3600">Hours</option>    <option value="86400">Days</option>    <option value="604800">Weeks</option>  </select><br /><br />  <button type="submit">Kick User</button><button type="reset">Reset</button></form>',
      title : 'Kick User',
      id : 'kickUserDialogue',
      width : 1000,
      oF : function() {
        var roomModList = [],
          i = 0;

        for (i = 0; i < roomList.length; i += 1) {
          if (modRooms[roomRef[roomList[i]]] > 0) {
            roomModList.push(roomIdRef[roomRef[roomList[i]]].roomName);
          }
        }

        $('#userName').autocomplete({ source: userList });
        $('#roomNameKick').autocomplete({ source: roomModList });

        $("#kickUserForm").submit(function() {
          var roomNameKick = $('#roomNameKick').val(),
            roomId = roomRef[roomNameKick],
            userName = $('#userName').val(),
            userId = userRef[userName],
            length = Math.floor(Number($('#time').val() * Number($('#interval > option:selected').attr('value'))));

          standard.kick(userId,roomId,length);

          return false; // Don't submit the form.
        });

        return false;
      }
    });

    return false;
  },

  /*** END Kick ***/




  /*** START Help ***/

  help : function() {
    dia.full({
      content : window.templates.help,
      title : 'helpDialogue',
      width : 1000,
      position : 'top',
      tabs : true
    });

    return false;
  },

  /*** END Help ***/




  /*** START Archive ***/

  archive : function(options) {
    dia.full({
      content : '<form id="archiveSearch" action="#" method="get" style="text-align: center;"><table style="text-align: center; margin-left: auto; margin-right: auto;"><thead><tr><th align="center"><small>Search Text:</small></th><th><small>Filter by User:</small></th><th><small>Results per Page:</small></th></tr></thead><tbody><tr><td><input type="text" id="searchText" name="searchText" style="margin-left: auto; margin-right: auto; text-align: left;" /></td><td><input type="text" id="searchUser" name="searchUser" style="margin-left: auto; margin-right: auto; text-align: left;" /></td><td><select id="resultLimit" name="resultLimit" style="margin-left: auto; margin-right: auto; text-align: left;"><option value="10">10</option><option value="25" selected="selected">25</option><option value="50">50</option><option value="100">100</option><option value="500">500</option></select></td></tr></tbody></table></form><br /><br /><table class="center"><thead><tr><th style="width: 20%;">User</th><th style="width: 20%;">Time</th><th style="width: 60%;">Message</th><th>-</th></tr></thead><tbody id="archiveMessageList"></tbody></table><br /><br /><div align="center"><button id="archivePrev"><< Prev</button><button id="export">Export</button><button id="archiveNext">Next >></button></div>',
      title : 'Archive',
      id : 'archiveDialogue',
      position : 'top',
      width : 1000,
      autoOpen : false
    });

    standard.archive({
      roomId: options.roomId,
      idMin: options.idMin,
      callback: function(data) {
        $('#archiveDialogue').dialog('open');
        $("#searchUser").autocomplete({
          source: userList,
          change : function() {
            standard.archive({
              idMax : options.idMax,
              idMin : options.idMin,
              roomId : options.roomId,
              userId : userRef[$('#searchUser').val()],
              search : $('#searchText').val(),
              maxResults : $('#resultLimit').val(),
            });
          }
        });

        return false;
      }
    });

    return false;
  },

  /*** END Archive ***/




  /*** START Copyright ***/

  copyright : function() {
    dia.full({
      content : window.templates.copyright,
      title : 'copyrightDialogue',
      width : 600,
      tabs : true
    });

    return false;
  }

  /*** END Copyright ***/
};

/*********************************************************
************************* END ***************************
************** Repeat-Action Popup Methods **************
*********************************************************/