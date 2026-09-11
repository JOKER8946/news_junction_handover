/*****************************************************
 * Common Javascript functions
 *****************************************************/

var chkFilterEmail = /\w{1,}[@][\w\-]{1,}([.]([\w\-]{1,})){1,3}$/;
var gData = [];
var gActAfter = '';

$(function () {
  $('#sidebarToggle').on('click', function (e) {
    e.preventDefault();
    $('body').toggleClass('sb-sidenav-toggled');
  });
});

function np() {
  return void (0);
}

function numCheck(e) {
  var keynum = (e.which) ? e.which : e.keyCode;
  return !((keynum == 8 || keynum == undefined || e.which == 0) ? null : String.fromCharCode(keynum).match(/[^0-9]/));
}

function openWin(x, width, height) {
  window.open(x, null, "width=" + width + ",height=" + height + ",menubar=0,toolbar=0,resizable=0,scrollbars=1");
}

function getParam(param) {
  return new URLSearchParams(window.location.search).get(param);
}

function getBootstrapBreakpoint() {
  var w = $(document).innerWidth();
  return (w < 768) ? 'xs' : ((w < 992) ? 'sm' : ((w < 1200) ? 'md' : 'lg'));
}

function clearForm(wchForm) {
  $('#' + wchForm)[0].reset();
  $('#panelStatus').html('');
  return false;
}

function copyToClipboard(text) {
  if (window.clipboardData && window.clipboardData.setData) {
    // Internet Explorer-specific code path to prevent textarea being shown while dialog is visible.
    return window.clipboardData.setData("Text", text);
  } else if (document.queryCommandSupported && document.queryCommandSupported("copy")) {
    var textarea = document.createElement("textarea");
    textarea.textContent = text;
    textarea.style.position = "fixed";  // Prevent scrolling to bottom of page in Microsoft Edge.
    document.body.appendChild(textarea);
    textarea.select();
    try {
      return document.execCommand("copy");  // Security exception may be thrown by some browsers.
    } catch (ex) {
      console.warn("Copy to clipboard failed.", ex);
      return prompt("Copy to clipboard: Ctrl+C, Enter", text);
    } finally {
      document.body.removeChild(textarea);
    }
  }
}

function chkEmailSignUp() {
  $('#panelStatusSignUp').html('');
  var signEmail = $('#signEmail').val();
  if (signEmail != '') {
    if (chkFilterEmail.test(signEmail)) {
      $.ajax({
        method: 'POST',
        url: 'process/get.section.general.php',
        data: { act: 'chkExist', signEmail: signEmail }
      })
        .done(function (response) {
          if (response != 'OK') {
            $('#panelStatusSignUp').html('<div class="text-danger animate__animated animate__flash">Error: This email address already exists!</div>');
          }
        });
    }
  }
}

function chkResetPassword() {
  var forgotLogin = $('#forgotLogin').val();
  if (forgotLogin == '') {
    $('#panelStatus').html('<div class="text-danger animate__animated animate__flash">Error: Login not entered!</div>');
    return false;
  }
  if (forgotLogin != '') {
    if (!(chkFilterEmail.test(forgotLogin))) {
      $('#panelStatus').html('<div class="text-danger animate__animated animate__flash">Error: Login Email is not valid!</div>');
      return false;
    }
  }
  $.ajax({
    url: 'process/get.section.general.php',
    method: 'POST',
    data: { act: 'resetPassword', email: forgotLogin }
  })
    .done(function (res) {
      $('#widget_B').html('<div class="my-3">If you have an account with us, password reset instructions have been sent to your registered email address.</div>');
      $('#widget_F').hide();
    });
  return false;
}

function chkResetPasswordConfirm() {
  var loginPwd1 = $('#loginPwd1').val();
  var loginPwd2 = $('#loginPwd2').val();
  var loginToken = $('#token').val();
  if (loginPwd1 == '') {
    $('#panelStatus').html('<div class="text-danger animate__animated animate__flash">Error: New Password not entered!</div>');
    return false;
  }
  if (loginPwd1 != loginPwd2) {
    $('#panelStatus').html('<div class="text-danger animate__animated animate__flash">Error: Passwords do not match!</div>');
    return false;
  }
  $.ajax({
    url: 'process/get.section.general.php',
    method: 'POST',
    data: { act: 'resetPasswordConfirm', loginPwd: loginPwd1, loginToken: loginToken }
  })
    .done(function (res) {addLanding
      $('#panelReset').html('<div class="my-3">' + res + '</div>');
    });
  return false;
}

function chkSignUp() {
  var signFullName = $('#signFullName').val();
  var signEmail = $('#signEmail').val();
  var signPwd1 = $('#signPwd1').val();
  var signPwd2 = $('#signPwd2').val();
  var signBusinessType = $('#signBusinessType').val();
  var captcha = $('[name=h-captcha-response]').val();
  if (signFullName == '') {
    $('#panelStatusSignUp').html('<div class="text-danger animate__animated animate__flash">Error: Full Name not entered!</div>');
    return false;
  }
  if (signEmail == '') {
    $('#panelStatusSignUp').html('<div class="text-danger animate__animated animate__flash">Error: Email not entered!</div>');
    return false;
  }
  if (signEmail != '') {
    if (!(chkFilterEmail.test(signEmail))) {
      $('#panelStatusSignUp').html('<div class="text-danger animate__animated animate__flash">Error: Email is not valid!</div>');
      return false;
    }
  }
  if (signPwd1 == '') {
    $('#panelStatusSignUp').html('<div class="text-danger animate__animated animate__flash">Error: Password is not entered!</div>');
    return false;
  }
  if (signPwd1 != signPwd2) {
    $('#panelStatusSignUp').html('<div class="text-danger animate__animated animate__flash">Error: Passwords do not match!</div>');
    return false;
  }
  if (signBusinessType == '') {
    $('#panelStatusSignUp').html('<div class="text-danger animate__animated animate__flash">Error: Business Type is not selected!</div>');
    return false;
  }
  if (captcha == '' || captcha == undefined) {
    $('#panelStatusSignUp').html('<div class="text-danger animate__animated animate__flash">Error: Please confirm if you are human!</div>');
    return false;
  }
  $.ajax({
    method: 'POST',
    url: 'process/get.section.general.php',
    data: { act: 'chkExist', signEmail: signEmail }
  })
    .done(function (response) {
      if (response == 'OK') {
        $('#contentLoader').show();
        $.ajax({
          url: 'process/get.section.general.php',
          method: 'POST',
          data: $('#frmSignUp').serializeArray()
        })
          .done(function (res) {
            $('#contentLoader').hide();
            if (res == 'OK') {
              $('#panelSignUp').html('Please check your email for further instructions<br>on how to activate your account!');
            } else {
              $('#panelStatusSignUp').html('<div class="text-danger animate__animated animate__flash">Error: Account could not be created!</div>');
            }
          });
      } else {
        $('#panelStatusSignUp').html('<div class="text-danger animate__animated animate__flash">Error: This email address already exists!</div>');
      }
    });
  return false;
}

function getCookieValue(a) {
  var b = document.cookie.match('(^|;)\\s*' + a + '\\s*=\\s*([^;]+)');
  return b ? b.pop() : '';
}

function removeSignedIn() {
  document.cookie = 'knoblyCreamUser=;Path=/;expires=Thu, 01 Jan 1970 00:00:01 GMT;';
}

function chkSignedInFrmHome() {
  var knoblyCreamUser = getCookieValue('knoblyCreamUser');
  if (knoblyCreamUser != '') {
    $.ajax({
      url: 'process/chk.logged.in.php',
      method: 'POST',
      data: { knoblyCreamUser: knoblyCreamUser },
    })
      .done(function (res) {
        if (res == 'OK') {
          window.location = 'dashboard.php';
        }
      });
  }
}

function chkSignedInFrmSplash() {
  var knoblyCreamUser = getCookieValue('knoblyCreamUser');
  if (knoblyCreamUser != '') {
    $.ajax({
      url: 'process/chk.logged.in.php',
      method: 'POST',
      data: { knoblyCreamUser: knoblyCreamUser },
    })
      .done(function (res) {
        if (res == 'OK') {
          window.location = 'dashboard.php';
        } else {
          window.location = 'signup.html?type=login';
        }
      });
  } else {
    window.location = 'signup.html?type=login';
  }
}

function chkLogin() {
  var signedIn = $('input[name=signedIn]:checked').val();
  var loginEmail = $('#loginEmail').val();
  var loginPwd = $('#loginPwd').val();

  //  bhuvanesh code
  var url = window.location.href;

  var urlObj = new URL(url);
  var parameters = urlObj.searchParams;

  var article_Id = parameters.get('article_Id');

  // var parameters = $.param.querystring(queryString);
  // var article_Id = parameters.article_Id !== undefined ? parameters.article_Id : null;

  if (loginEmail == '' || loginPwd == '') {
    alert('Both Login and Password are required!');
    return false;
  }
  if (loginEmail != '') {
    if (!(chkFilterEmail.test(loginEmail))) {
      alert('Your Email is not valid!');
      return false;
    }
  }
  $.ajax({
    url: 'process/chk.login.php',
    method: 'POST',
    data: { email: loginEmail, pwd: loginPwd },
  })
    .done(function (res) {
      if (res == 'notActivated') {
        alert('ERROR: Account is not activated!');
      } else if (res != '') {
        if (res.substring(0, 2) == 'OK') {
          if (signedIn) {
            var now = new Date();
            now.setMonth(now.getMonth() + 1);
            document.cookie = "knoblyCreamUser=" + res.substring(3) + ";path=/;expires=" + now.toUTCString() + ";"
          } else {
            removeSignedIn();
          }

          // bhuvanesh code
          if (article_Id != null) {
            window.location = '/article.php?article_id=' + article_Id;
          }
          else {
            window.location = 'dashboard.php';
          }

        } else {
          alert('ERROR: User or Password is incorrect!');
        }
      } else {
        alert('ERROR: User or Password is incorrect!');
      }
    });
  return false;
}

function goSection(secName, elm, act) {
  if (secName != 'collection') gActAfter = '';
  $('#panelContent').hide();
  $('#contentLoader').show();
  var bootstrapBreakpoint = getBootstrapBreakpoint();
  if (act == undefined) act = '';
  if (elm != undefined) $('.nav-link').removeClass('active');
  $(elm).addClass('active');
  if (secName == 'create') {
    try {
      tinymce.remove();
    } catch (e) { }
  }
  $('#panelContent').html('').show().load('process/get.section.' + secName + '.php', { act: act, actAfter: gActAfter }, function () { $('#contentLoader').hide(); $('.panelFeeds').scrollTop(0); });
  if (bootstrapBreakpoint == 'xs' || bootstrapBreakpoint == 'sm') $('body').toggleClass('sb-sidenav-toggled');
}

function generateSM() {
  var buttons = $('.sbg-button');
  for (let i = 0; i < buttons.length; i++) {
    var button = buttons[i];
    var data = $(button).closest('.data');
    var smType = $(button).data('sbg-network');
    var specs = 'height=' + $(button).data('sbg-height') + ',width=' + $(button).data('sbg-width') + ',top=' + Math.max(0, (screen.height - $(button).data('sbg-height')) / 2)
      + ',left=' + Math.max(0, (screen.width - $(button).data('sbg-width')) / 2)
      + ',status=0,toolbar=0,directories=0,location=0,menubar=0,resizable=1,scrollbars=1';
    var windowName = 'sbg-window-' + Math.random();
    switch (smType) {
      case 'facebook':
        var url = buildUrl('https://www.facebook.com/sharer.php', {
          's': 100,
          'p[url]': data.data('feed-url'),
          'p[title]': data.data('feed-title'),
          'p[summary]': $(button).data('sbg-summary'),
          'p[images][0]': $(button).data('sbg-image')
        });
        break;
      case 'twitter':
        var url = buildUrl('https://twitter.com/intent/tweet', {
          'text': data.data('feed-title') + '' + data.data('feed-url'),
          'via': $(button).data('sbg-via'),
          'hashtags': $(button).data('sbg-hashtags')
        });
        break;
      case 'linkedin':
        var url = buildUrl('https://www.linkedin.com/shareArticle', {
          'mini': 'true',
          'url': data.data('feed-url'),
          'title': data.data('feed-title')
        });
        break;
      case 'pinterest':
        var url = buildUrl('https://www.pinterest.com/pin/create/button/', {
          'url': data.data('feed-url'),
          'media': $(button).data('sbg-media'),
          'description': data.data('feed-desc')
        });
        break;
    }
    $(button).data('sbg-1', url);
    $(button).data('sbg-2', windowName);
    $(button).data('sbg-3', specs);
  }

  $('.sbg-button').click(function () {
    openSM(this);
  });

  function buildUrl(url, parameters) {
    var qs = "";
    for (var key in parameters) {
      var value = parameters[key];
      if (!value) { continue }
      value = value.toString().split('\"').join('"');
      qs += key + "=" + encodeURIComponent(value) + "&";
    }
    if (qs.length > 0) {
      qs = qs.substring(0, qs.length - 1); //chop off last "&"
      url = url + "?" + qs;
    }
    return url;
  }
}

function generateSM_old() {
  var buttons = $('.sbg-button');
  for (let i = 0; i < buttons.length; i++) {
    var button = buttons[i];
    var smType = $(button).data('sbg-network');
    var specs = 'height=' + $(button).data('sbg-height') + ',width=' + $(button).data('sbg-width') + ',top=' + Math.max(0, (screen.height - $(button).data('sbg-height')) / 2)
      + ',left=' + Math.max(0, (screen.width - $(button).data('sbg-width')) / 2)
      + ',status=0,toolbar=0,directories=0,location=0,menubar=0,resizable=1,scrollbars=1';
    var windowName = 'sbg-window-' + Math.random();
    switch (smType) {
      case 'facebook':
        var url = buildUrl('https://www.facebook.com/sharer.php', {
          's': 100,
          'p[url]': $(button).data('sbg-url'),
          'p[title]': $(button).data('sbg-title'),
          'p[summary]': $(button).data('sbg-summary'),
          'p[images][0]': $(button).data('sbg-image')
        });
        break;
      case 'twitter':
        var url = buildUrl('https://twitter.com/intent/tweet', {
          'text': $(button).data('sbg-text'),
          'via': $(button).data('sbg-via'),
          'hashtags': $(button).data('sbg-hashtags')
        });
        break;
      case 'linkedin':
        var url = buildUrl('https://www.linkedin.com/shareArticle', {
          'mini': 'true',
          'url': $(button).data('sbg-url'),
          'title': $(button).data('sbg-title'),
          'source': $(button).data('sbg-source'),
          'summary': $(button).data('sbg-summary')
        });
        break;
      case 'pinterest':
        var url = buildUrl('https://www.pinterest.com/pin/create/button/', {
          'url': $(button).data('sbg-url'),
          'media': $(button).data('sbg-media'),
          'description': $(button).data('sbg-description')
        });
        break;
    }
    $(button).data('sbg-1', url);
    $(button).data('sbg-2', windowName);
    $(button).data('sbg-3', specs);
  }

  $('.sbg-button').click(function () {
    openSM(this);
  });

  function buildUrl(url, parameters) {
    var qs = "";
    for (var key in parameters) {
      var value = parameters[key];
      if (!value) { continue }
      value = value.toString().split('\"').join('"');
      qs += key + "=" + encodeURIComponent(value) + "&";
    }
    if (qs.length > 0) {
      qs = qs.substring(0, qs.length - 1); //chop off last "&"
      url = url + "?" + qs;
    }
    return url;
  }
}

function openSM(wchElm) {
  window.open($(wchElm).data('sbg-1'), $(wchElm).data('sbg-2'), $(wchElm).data('sbg-3'));
}

function chkUpdateProfile() {
  $('#panelStatus').html('');
  var userName = $('#userName').val();
  var userEmail = $('#userEmail').val();
  var userCompany = $('#userCompany').val();
  var userCategoryId = $('#userCategoryId').val();
  var userWebsite = $('#userWebsite').val();
  var userBio = $('#userBio').val();
  if (userName == '') {
    $('#panelStatus').html('<div class="alert alert-danger animate__animated animate__flash" role="alert">Error: Full Name not entered!</div>');
    return false;
  }
  if (userEmail == '') {
    $('#panelStatus').html('<div class="alert alert-danger animate__animated animate__flash" role="alert">Error: Email not entered!</div>');
    return false;
  }
  $.ajax({
    method: 'POST',
    url: 'process/get.section.account.php',
    data: { act: 'chkExist', email: userEmail }
  })
    .done(function (response) {
      if (response == 'OK') {
        $.ajax({
          method: 'POST',
          url: 'process/get.section.account.php',
          data: { act: 'updateProfile', userName: userName, userEmail: userEmail, userCompany: userCompany, userCategoryId: userCategoryId, userWebsite: userWebsite,userBio:userBio }
        })
          .done(function (response) {
            if (response == 'OK') {
              $('#panelStatus').html('<div class="alert alert-primary" role="alert">Profile has been updated!</div>');
            } else {
              $('#panelStatus').html('<div class="alert alert-danger animate__animated animate__flash" role="alert">Error: Profile could not be updated!</div>');
            }
          });
      } else {
        $('#panelStatus').html('<div class="alert alert-danger animate__animated animate__flash" role="alert">Error: This email is already being used!</div>');
      }
    });
  return false;
}

function chkUpdatePassword() {
  $('#panelStatus').html('');
  var userPassCurrent = $('#userPassCurrent').val();
  var userPassNew1 = $('#userPassNew1').val();
  var userPassNew2 = $('#userPassNew2').val();
  if (userPassCurrent == '') {
    $('#panelStatus').html('<div class="alert alert-danger animate__animated animate__flash" role="alert">Error: Current Password not entered!</div>');
    return false;
  }
  if (userPassNew1 == '') {
    $('#panelStatus').html('<div class="alert alert-danger animate__animated animate__flash" role="alert">Error: New password not entered!</div>');
    return false;
  }
  if (userPassNew2 == '') {
    $('#panelStatus').html('<div class="alert alert-danger animate__animated animate__flash" role="alert">Error: Retype Password not entered!</div>');
    return false;
  }
  if (userPassNew1 != userPassNew2) {
    $('#panelStatus').html('<div class="alert alert-danger animate__animated animate__flash" role="alert">Error: Retype Password dos not match!</div>');
    return false;
  }
  $.ajax({
    method: 'POST',
    url: 'process/get.section.account.php',
    data: { act: 'updatePass', userPassCurrent: userPassCurrent, userPassNew: userPassNew1 }
  })
    .done(function (response) {
      if (response == 'OK') {
        $('#panelStatus').html('<div class="alert alert-primary" role="alert">Password has been updated!</div>');
      } else {
        $('#panelStatus').html('<div class="alert alert-danger animate__animated animate__flash" role="alert">Error: Incorrect Current password entered!</div>');
      }
    });
  return false;
}

function chkUpdateSubdomain() {
  var userSubdomain = $('#userSubdomain').val();
  $.ajax({
    method: 'POST',
    url: 'process/get.section.settings.php',
    data: { act: 'chkExistSubdomain', subdomain: userSubdomain }
  })
    .done(function (response) {
      if (response == 'OK') {
        $.ajax({
          method: 'POST',
          url: 'process/get.section.settings.php',
          data: { act: 'editSubdomain', subdomain: userSubdomain }
        })
          .done(function (response) {
            if (response == 'OK') {
              $('#panelStatus').html('<div class="alert alert-primary" role="alert">Subdomain has been updated!</div>');
            } else {
              $('#panelStatus').html('<div class="alert alert-danger animate__animated animate__flash" role="alert">Error: Subdomain could not be updated!</div>');
            }
          });
      } else {
        $('#panelStatus').html('<div class="alert alert-danger animate__animated animate__flash" role="alert">Error: This Subdomain is already being used!</div>');
      }
    });
  return false;
}

function chkUpdateView() {
  $('#panelStatus').html('');
  var userSidePanel = $('input[name=userSidePanel]:checked').val();
  var userSidePanelContent = tinymce.get('userSidePanelContent').getContent();
  tinyMCE.triggerSave();
  $.ajax({
    method: 'POST',
    url: 'process/get.section.settings.php',
    data: { act: 'editView', userSidePanel: userSidePanel, userSidePanelContent: userSidePanelContent }
  })
    .done(function (response) {
      if (response == 'OK') {
        $('#panelStatus').html('<div class="alert alert-primary" role="alert">View Page Settings have been updated!</div>');
      } else {
        $('#panelStatus').html('<div class="alert alert-danger animate__animated animate__flash" role="alert">Error: Could not update View Page Settings!</div>');
      }
    });
  return false;
}

function chkUpdateNewsletter() {
  $('#panelStatus').html('');
  var userNewsletterTitle = $('#userNewsletterTitle').val();
  var userNewsletterLogo = $('#userNewsletterLogo').val();
  if (userNewsletterTitle == '') {
    $('#panelStatus').html('<div class="alert alert-danger animate__animated animate__flash" role="alert">Error: Newsletter Title not entered!</div>');
    return false;
  }
  if (userNewsletterLogo == '') {
    $('#panelStatus').html('<div class="alert alert-danger animate__animated animate__flash" role="alert">Error: Newsletter Logo not uploaded!</div>');
    return false;
  }
  var formData = new FormData();
  formData.append('act', 'editNewsletter');
  formData.append('userNewsletterTitle', userNewsletterTitle);
  formData.append('userNewsletterLogo', $('#userNewsletterLogo')[0].files[0]);
  $.ajax({
    method: 'POST',
    url: 'process/get.section.settings.php',
    data: formData,
    processData: false,
    contentType: false,
    enctype: 'multipart/form-data'
  })
    .done(function (response) {
      if (response == 'OK') {
        $('#panelStatus').html('<div class="alert alert-primary" role="alert">Newsletter Settings have been updated!</div>');
      } else {
        $('#panelStatus').html('<div class="alert alert-danger animate__animated animate__flash" role="alert">Error: Could not update Newsletter Settings!</div>');
      }
    });
  return false;
}

function chkUpdateSendGrid() {
  $('#panelStatus').html('');
  var userSendGridKey = $('#userSendGridKey').val();
  var userSendGridSuppressionId = $('#userSendGridSuppressionId').val();
  if (userSendGridKey == '') {
    $('#panelStatus').html('<div class="alert alert-danger animate__animated animate__flash" role="alert">Error: Send Grid Key not entered!</div>');
    return false;
  }
  if (userSendGridSuppressionId == '') {
    $('#panelStatus').html('<div class="alert alert-danger animate__animated animate__flash" role="alert">Error: Send Grid Suppression ID not entered!</div>');
    return false;
  }
  $.ajax({
    method: 'POST',
    url: 'process/get.section.settings.php',
    data: { act: 'editSendGridKey', userSendGridKey: userSendGridKey, userSendGridSuppressionId: userSendGridSuppressionId }
  })
    .done(function (response) {
      if (response == 'OK') {
        $('#panelStatus').html('<div class="alert alert-primary" role="alert">Send Grid Key has been updated!</div>');
      } else {
        $('#panelStatus').html('<div class="alert alert-danger animate__animated animate__flash" role="alert">Error: Could not update Send Grid Key!</div>');
      }
    });
  return false;
}

function chkSendNewsletter(wchNewsId) {
  var sendGridSenderId = $('#sendGridSenderId_' + wchNewsId).val();
  var sendGridListId = $('#sendGridListId_' + wchNewsId).val();
  var sendGridSubject = $('#sendGridSubject_' + wchNewsId).val();
  if (sendGridSenderId == '') {
    alert('Sender not selected!');
    return false;
  }
  if (sendGridListId == '') {
    alert('List not selected!');
    return false;
  }
  if (sendGridSubject == '') {
    alert('Subject not entered!');
    return false;
  }
  if (confirm('Are you sure you want to send this Newsletter?')) {
    $.ajax({
      method: 'POST',
      url: 'process/get.section.newsletter.php',
      data: { act: 'sendNewsletter', newsId: wchNewsId, sendGridSenderId: sendGridSenderId, sendGridListId: sendGridListId, sendGridSubject: sendGridSubject }
    })
      .done(function (response) {
        if (response == 'OK') {
          alert('Newsletter has been sent!');
        } else {
          alert('ERROR: Newsletter could not be sent!');
        }
      });
  }
  return false;
}

function chkCreamShare() {
  $('#panelStatus').html('');
  var shareEmail = $('#shareEmail').val();
  var feedId = $('#feedId').val();
  var feedTitle = $('#feedTitle').val();
  var feedURL = $('#feedURL').val();
  var feedDesc = $('#feedDesc').val();
  if (shareEmail == '') {
    $('#panelStatus').html('<div class="text-danger animate__animated animate__flash">Error: User not entered!</div>');
    return false;
  }
  $.ajax({
    method: 'POST',
    url: 'process/get.section.general.php',
    data: { act: 'chkExistUser', chkEmail: shareEmail }
  })
    .done(function (response) {
      if (response != '') {
        $.ajax({
          method: 'POST',
          url: 'process/get.section.utils.php',
          data: { act: 'creamShared', shareId: response, feedId: feedId, feedTitle: feedTitle, feedURL: feedURL, feedDesc: feedDesc }
        })
          .done(function (response) {
            if (response == 'OK') {
              $('#widget_B').html('Cream<i>Share</i> successful!');
              $('#widget_F').hide();
              goSection('collection');
            } else {
              $('#panelStatus').html('<div class="text-danger animate__animated animate__flash">Error: Cream<i>Share</i> failed!</div>');
            }
          });
      } else {
        $('#panelStatus').html('<div class="text-danger animate__animated animate__flash">Error: User does not exist!</div>');
      }
    });
  return false;
}

function chkAddExternalFeed() {
  $('#panelStatus').html('');
  var categoryId = $('#categoryId').val();
  var feedURL = $('#feedURL').val();
  if (categoryId == '') {
    $('#panelStatus').html('<div class="text-danger animate__animated animate__flash">Error: Category not selected!</div>');
    return false;
  }
  if (feedURL == '') {
    $('#panelStatus').html('<div class="text-danger animate__animated animate__flash">Error: Feed URL not entered!</div>');
    return false;
  }
  $.ajax({
    method: 'POST',
    url: 'process/get.section.settings.php',
    data: { act: 'addedExternalFeed', categoryId: categoryId, feedURL: feedURL }
  })
    .done(function (response) {
      if (response == 'OK') {
        $('#panelStatus').html('<div class="text-primary">Feed has been added!</div>');
        goSection('settings');
      } else {
        $('#panelStatus').html('<div class="text-danger animate__animated animate__flash">Error: Feed could not be added!</div>');
      }
    });
  return false;
}

function chkRestoreCollection() {
  $('#panelStatus').html('');
  $.ajax({
    method: 'POST',
    url: 'process/get.section.collection.php',
    data: $('#frmDel').serializeArray()
  })
    .done(function (response) {
      if (response == 'OK') {
        $('#widget_B').html('Item has been restored!');
        $('#widget_F').hide();
        goSection('collection');
      } else {
        $('#panelStatus').html('<small>Error: Could not restore Item!</small>');
      }
    });
  return false;
}

function chkArchiveCollection() {
  $('#panelStatus').html('');
  $.ajax({
    method: 'POST',
    url: 'process/get.section.collection.php',
    data: $('#frmDel').serializeArray()
  })
    .done(function (response) {
      if (response == 'OK') {
        $('#widget_B').html('Item has been archived!');
        $('#widget_F').hide();
        goSection('collection');
      } else {
        $('#panelStatus').html('<small>Error: Could not archive Item!</small>');
      }
    });
  return false;
}

function chkDelFeed() {
  $('#panelStatus').html('');
  $.ajax({
    method: 'POST',
    url: 'process/get.section.settings.php',
    data: $('#frmDel').serializeArray()
  })
    .done(function (response) {
      if (response == 'OK') {
        $('#widget_B').html('Feed has been deleted!');
        $('#widget_F').hide();
        goSection('settings');
      } else {
        $('#panelStatus').html('<small>Error: Could not delete Issue!</small>');
      }
    });
  return false;
}

function chkDelCollection() {
  $('#panelStatus').html('');
  $.ajax({
    method: 'POST',
    url: 'process/get.section.collection.php',
    data: $('#frmDel').serializeArray()
  })
    .done(function (response) {
      if (response == 'OK') {
        $('#widget_B').html('Item has been deleted!');
        $('#widget_F').hide();
        goSection('collection');
      } else {
        $('#panelStatus').html('<small>Error: Could not delete Item!</small>');
      }
    });
  return false;
}

function chkDelNewsletter() {
  $('#panelStatus').html('');
  $.ajax({
    method: 'POST',
    url: 'process/get.section.newsletter.php',
    data: $('#frmDel').serializeArray()
  })
    .done(function (response) {
      if (response == 'OK') {
        $('#widget_B').html('Newsletter has been deleted!');
        $('#widget_F').hide();
        goSection('newsletter');
      } else {
        $('#panelStatus').html('<small>Error: Could not delete Newsletter!</small>');
      }
    });
  return false;
}

function addCollection() {
  $('#buttonAddCollection').hide();
  gData = [];
  $.each($("input[name='feedData']:checked"), function () {
    var data = $(this).closest('.data');
    gData.push({
      title: data.data('feed-title'),
      url: data.data('feed-url'),
      desc: data.data('feed-desc'),
      publisher: data.data('feed-publisher')
    });
  });
  if (gData.length) $('#buttonAddCollection').show();
}

function chkAddCollection() {
  if (gData.length) {
    $.ajax({
      method: 'POST',
      url: 'process/get.section.curated.php',
      data: { act: 'addCollection', data: JSON.stringify(gData) }
    })
      .done(function (response) {
        if (response == 'OK') {
          alert('Selected items have been added in your collection!');
        } else {
          alert('ERROR: Could not add in your collection!');
        }
      });
  }
  return false;
}

function addCreamFeed() {
  $('#buttonAddCreamFeed').hide();
  gData = [];
  $.each($("input[name='feedData']:checked"), function () {
    var data = $(this).val();
    gData.push({
      id: data
    });
  });
  if (gData.length) $('#buttonAddCreamFeed').show();
}

function addNewsletter() {
  $('#buttonAddNewsletter').hide();
  gData = [];
  $.each($("input[name='feedData']:checked"), function () {
    var data = $(this).closest('.data');
    gData.push({
      id: data.data('feed-id')
    });
  });
  if (gData.length) $('#buttonAddNewsletter').show();
}

function addLanding() {
  $('#buttonAddLanding').hide();
  gData = [];
  $.each($("input[name='landingData']:checked"), function () {
    var data = $(this).data('feed-id');
    gData.push({
      id: data
    });
  });
  if (gData.length) $('#buttonAddLanding').show();
}

function addCovid() {
  $('#buttonAddCovid').hide();
  gData = [];
  $.each($("input[name='covidData']:checked"), function () {
    var data = $(this).data('feed-id');
    gData.push({
      id: data
    });
  });
  if (gData.length) $('#buttonAddCovid').show();
}

function chkAddNewsletter() {
  if (gData.length) {
    $.ajax({
      method: 'POST',
      url: 'process/get.section.collection.php',
      data: { act: 'addNewsletter', data: JSON.stringify(gData) }
    })
      .done(function (response) {
        if (response == 'OK') {
          goSection('newsletter');
        } else if (response == 'NO') {
          alert('You have exceeded the number of allowed newsletter in a month!\nGo to My Account to upgrade.');
        } else {
          alert('ERROR: Could not generate newsletter!');
        }
      });
  }
  gData = [];
  return false;
}

function chkAddLanding() {
  if (gData.length) {
    $.ajax({
      method: 'POST',
      url: 'process/get.section.collection.php',
      data: { act: 'addLanding', data: JSON.stringify(gData) }
    })
      .done(function (response) {
        if (response == 'OK') {
          goSection('collection');
          alert('Added in Showcase!');
        } else {
          alert('ERROR: Could not add in Showcase!');
        }
      });
  }
  gData = [];
  return false;
}

function chkAddCovid() {
  if (gData.length) {
    $.ajax({
      method: 'POST',
      url: 'process/get.section.collection.php',
      data: { act: 'addCovid', data: JSON.stringify(gData) }
    })
      .done(function (response) {
        if (response == 'OK') {
          goSection('collection');
          alert('Added in Covid Page!');
        } else {
          alert('ERROR: Could not add in Covid page!');
        }
      });
  }
  gData = [];
  return false;
}

function chkAddCreamFeed() {
  if (gData.length) {
    $.ajax({
      method: 'POST',
      url: 'process/get.section.settings.php',
      data: { act: 'addedCreamFeed', data: JSON.stringify(gData) }
    })
      .done(function (response) {
        if (response == 'OK') {
          $('#panelStatus').html('<div class="text-primary">Feed has been added!</div>');
          goSection('settings');
        } else {
          alert('ERROR: Could not add Cream Feed!');
        }
      });
  }
  gData = [];
  return false;
}

function chkCreatePost() {
  $('#panelStatus').html('');
  var postTitle = $('#postTitle').val();
  var postBody = tinymce.get('postBody').getContent();
  if (postTitle == '') {
    alert('Error: Title not entered!');
    return false;
  }
  if (postBody == '') {
    alert('Error: Post is empty!');
    return false;
  }
  tinyMCE.triggerSave();
  tinymce.activeEditor.uploadImages(function (success) {
    var articleTag = $('input[name=articleTag]:checked').map(function () { return this.value; }).get();
    var formData = new FormData();
    formData.append('act', 'createPost');
    formData.append('postTitle', postTitle);
    formData.append('postBody', postBody);
    formData.append('articleTag', articleTag);
    formData.append('datePublished', $('#datePublished').val());
    formData.append('pageViewStart', $('#pageViewStart').val());
    formData.append('author', $('#author').val());
    formData.append('isArchive', $('input[name=isArchive]:checked').val());
    formData.append('isReadMore', $('input[name=isReadMore]:checked').val());
    formData.append('readMoreTxt', $('#readMoreTxt').val());
    formData.append('readMoreResponse', $('#readMoreResponse').val());
    formData.append('readMoreEmail', $('#readMoreEmail').val());
    formData.append('isMandatoryCompany', $('input[name=isMandatoryCompany]:checked').val());
    formData.append('isMandatoryEmail', $('input[name=isMandatoryEmail]:checked').val());
    formData.append('isMandatoryMobile', $('input[name=isMandatoryMobile]:checked').val());
    formData.append('uploadCover', $('#uploadCover')[0].files[0]);
    $.ajax({
      method: "POST",
      url: 'process/get.section.create.php',
      data: formData,
      processData: false,
      contentType: false,
      enctype: 'multipart/form-data'
    })
      .done(function (msg) {
        if (msg == 'OK') {
          tinymce.get('postBody').setContent('');
          $('#rss').html('Post has been created!<br><br><button class="btn btn-primary" onclick="goSection(\'create\')">Add a New Post</button>');
        }
      });
  });
  return false;
}

function chkEditPost() {
  $('#panelStatus').html('');
  var postTitle = $('#postTitle').val();
  var postBody = tinymce.get('postBody').getContent();
  if (postTitle == '') {
    $('#panelStatus').html('<div class="text-danger animate__animated animate__flash" role="alert">Error: Title not entered!</div>');
    return false;
  }
  if (postBody == '') {
    $('#panelStatus').html('<div class="text-danger animate__animated animate__flash" role="alert">Error: Post is empty!</div>');
    return false;
  }
  tinyMCE.triggerSave();
  tinymce.activeEditor.uploadImages(function (success) {
    var articleTag = $('input[name=articleTag]:checked').map(function () { return this.value; }).get();
    var formData = new FormData();
    formData.append('act', 'edit');
    formData.append('editId', $('#editId').val());
    formData.append('postTitle', postTitle);
    formData.append('postBody', postBody);
    formData.append('articleTag', articleTag);
    formData.append('datePublished', $('#datePublished').val());
    formData.append('pageViewStart', $('#pageViewStart').val());
    formData.append('author', $('#author').val());
    formData.append('isArchive', $('input[name=isArchive]:checked').val());
    formData.append('isReadMore', $('input[name=isReadMore]:checked').val());
    formData.append('readMoreTxt', $('#readMoreTxt').val());
    formData.append('readMoreResponse', $('#readMoreResponse').val());
    formData.append('readMoreEmail', $('#readMoreEmail').val());
    formData.append('isMandatoryCompany', $('input[name=isMandatoryCompany]:checked').val());
    formData.append('isMandatoryEmail', $('input[name=isMandatoryEmail]:checked').val());
    formData.append('isMandatoryMobile', $('input[name=isMandatoryMobile]:checked').val());
    formData.append('uploadCover', $('#uploadCover')[0].files[0]);
    $.ajax({
      method: "POST",
      url: 'process/get.section.collection.php',
      data: formData,
      processData: false,
      contentType: false,
      enctype: 'multipart/form-data'
    })
      .done(function (msg) {
        if (msg == 'OK') {
          $('#widget_B').html('Item has been edited!');
          $('#widget_F').hide();
          goSection('collection');
        }
      });
  });
  return false;
}

function chkSendPost() {
  $('#panelStatus').html('');
  var sendName = $('#sendName').val();
  var sendEmail = $('#sendEmail').val();
  var sendBody = tinymce.get('sendBody').getContent();
  if (sendName == '') {
    $('#panelStatus').html('<div class="text-danger animate__animated animate__flash" role="alert">Error: Recipient Name not entered!</div>');
    return false;
  }
  if (sendEmail == '') {
    $('#panelStatus').html('<div class="text-danger animate__animated animate__flash" role="alert">Error: Recipient Email not entered!</div>');
    return false;
  }
  if (sendEmail != '') {
    if (!(chkFilterEmail.test(sendEmail))) {
      $('#panelStatus').html('<div class="text-danger animate__animated animate__flash" role="alert">Error: Recipient Email is not valid!</div>');
      return false;
    }
  }
  if (sendBody == '') {
    $('#panelStatus').html('<div class="text-danger animate__animated animate__flash" role="alert">Error: Email body is empty!</div>');
    return false;
  }
  tinyMCE.triggerSave();
  tinymce.activeEditor.uploadImages(function (success) {
    var formData = new FormData();
    formData.append('act', 'sendPost');
    formData.append('editId', $('#editId').val());
    formData.append('sendName', sendName);
    formData.append('sendEmail', sendEmail);
    formData.append('sendBody', sendBody);
    $.ajax({
      method: "POST",
      url: 'process/get.section.collection.php',
      data: formData,
      processData: false,
      contentType: false,
      enctype: 'multipart/form-data'
    })
      .done(function (msg) {
        if (msg == 'OK') {
          $('#widget_B').html('Item has been emailed!');
          $('#widget_F').hide();
        }
      });
  });
  return false;
}

function getBusinessType() {
  $.ajax({
    url: 'process/get.section.general.php',
    method: 'POST',
    data: { act: 'getBusinessType' }
  })
    .done(function (res) {
      $("#signBusinessType").append(new Option('Select business type', ''));
      var returnArr = JSON.parse(res);
      $.each(returnArr, function (index, value) {
        $("#signBusinessType").append(new Option(value[1], value[0]));
      });
      $("#signBusinessType").append(new Option('Other', '0'));
    });
}

function getCreamFeeds() {
  $('#panelStatus').html('');
  $('#buttonAddCreamFeed').hide();
  var categoryId = $('#categoryId').val();
  if (categoryId == '') {
    $('#panelCreamFeeds').html('');
  } else {
    $.ajax({
      url: 'process/get.section.settings.php',
      method: 'POST',
      data: { act: 'getCreamFeeds', categoryId: categoryId }
    })
      .done(function (res) {
        $('#panelCreamFeeds').html(res);
      });
  }
}


function chkGenerateArticle(action) {
  $('#actionType').val(action);

  // Get form values and trim whitespace
  var articleHeadline = $('#articleHeadline').val().trim();
  var articleObjective = $('#articleObjective').val().trim();
  var articleTargetGroup = $('#articleTargetGroup').val().trim();
  var articleKeywords = $('#articleKeywords').val().trim();
  var articleNumWords = $('#articleNumWords').val().trim();
  var articleNumImages = $('#articleNumImages').val().trim();

  // Check for empty fields and collect errors
  var errors = [];
  if (!articleHeadline) errors.push('Headline not entered!');
  if (!articleObjective) errors.push('Objective not entered!');
  if (!articleTargetGroup) errors.push('Target Group not entered!');
  if (!articleKeywords) errors.push('Keywords not entered!');
  if (!articleNumWords) errors.push('Number of words not entered!');
  // if (!articleNumImages) errors.push('Number of Pictures/Graphics/etc. not entered!');

  // Display errors if any
  if (errors.length > 0) {
    $('#panelStatusRequestArticle').html(
      '<div class="text-danger animate__animated animate__flash">Error: ' + errors.join(' ') + '</div>'
    );
    return false;
  }

  $('#contentLoader').show();

  // Perform AJAX request
  $.ajax({
    url: 'process/get.section.request.article.php',
    method: 'POST',
    data: serializeFormExcludingField('#frmArticle', 'sendRequest')
  })
    .done(function (res) {
      $('#contentLoader').hide();
      $('#panelRequestArticleHeader').hide();
      $('#panelRequestArticle').html(res);
    })
    .fail(function () {
      $('#contentLoader').hide();
      $('#panelStatusRequestArticle').html('<div class="text-danger animate__animated animate__flash">Error: Failed to process request.</div>');
    });

  return false; // Prevent default form submission
}

function serializeFormExcludingField(formSelector, excludeName) {
  var form = $(formSelector);
  var serializedArray = form.serializeArray();
  var filteredArray = serializedArray.filter(function (item) {
    return item.name !== excludeName;
  });

  // Convert the filtered array back to a query string
  return $.param(filteredArray);
}


function chkRequestArticle() {
  var articleHeadline = $('#articleHeadline').val();
  var articleObjective = $('#articleObjective').val();
  var articleTargetGroup = $('#articleTargetGroup').val();
  var articleKeywords = $('#articleKeywords').val();
  var articleNumWords = $('#articleNumWords').val();
  var articleNumImages = $('#articleNumImages').val();
  if (articleHeadline == '') {
    $('#panelStatusRequestArticle').html('<div class="text-danger animate__animated animate__flash">Error: Headline not entered!</div>');
    return false;
  }
  if (articleObjective == '') {
    $('#panelStatusRequestArticle').html('<div class="text-danger animate__animated animate__flash">Error: Objective not entered!</div>');
    return false;
  }
  if (articleTargetGroup == '') {
    $('#panelStatusRequestArticle').html('<div class="text-danger animate__animated animate__flash">Error: Target Group not entered!</div>');
    return false;
  }
  if (articleKeywords == '') {
    $('#panelStatusRequestArticle').html('<div class="text-danger animate__animated animate__flash">Error: Keywords not entered!</div>');
    return false;
  }
  if (articleNumWords == '') {
    $('#panelStatusRequestArticle').html('<div class="text-danger animate__animated animate__flash">Error: Number of words not entered!</div>');
    return false;
  }
  // if (articleNumImages == '') {
  //   $('#panelStatusRequestArticle').html('<div class="text-danger animate__animated animate__flash">Error: Number of Pictures/Graphics/etc. not entered!</div>');
  //   return false;
  // }
  $('#contentLoader').show();
  $.ajax({
    url: 'process/get.section.request.article.php',
    method: 'POST',
    data: $.param(serializeFormExcludingField('#frmArticle', 'generateArticle'))

  })
    .done(function (res) {
      $('#contentLoader').hide();
      $('#panelRequestArticleHeader').hide();
      $('#panelRequestArticle').html(res);
    });
}

function authPopup(provider) {
  var authWindow = window.open('../process/social.callback.php?provider=' + provider, 'authWindow', 'width=750,height=450,scrollbars=yes');
  window.closeAuthWindow = function () {
    authWindow.close();
  }
  return false;
}

function removeShowcase(wchId) {
  if (wchId != '') {
    $.ajax({
      url: 'process/get.section.collection.php',
      method: 'POST',
      data: { act: 'removeShowcase', collectionId: wchId }
    })
      .done(function (res) {
        $('#panelShowcase_' + wchId).html('<label class="btn btn-outline-danger m-0 mr-3 px-2 py-1 float-left" style="user-select:none"><input type="checkbox" name="landingData" data-feed-id="' + wchId + '" onclick="addLanding()"> Select for Showcase</label>');
      });
  }
}

function fetchProfessionData() {
  var profession = $('#profession').val();

  if (profession === "") {
    $('#response').html('<div class="alert alert-warning">Please select a profession.</div>');
    return;
  }

  $.ajax({
    url: 'process/get.section.settings.php', // Replace with your server endpoint URL
    type: 'POST',
    data: {
      act: 'genaiProfession',
      profession: profession
    },
    success: function (response) {
      if (response == 'OK') {
        $('#response').html('<div class="alert alert-success"> Profession updated successfully </div>');
      }
      else {
        $('#response').html('<div class="alert alert-danger">Problem occurred: ' + response + '</div>');
      }
    },
    error: function (xhr, status, error) {
      $('#response').html('<div class="alert alert-danger">An error occurred: ' + error + '</div>');
    }
  });
}