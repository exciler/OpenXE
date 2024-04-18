

<form action="" id="frmlogin" method="post"><br>
  <div class="field">
  <input style="display:none;min-width:200px;" id="chtype" type="button" value="Login mit Username / PW" />
  </div>
  <div class="field">
    <label for="username">Benutzer:</label>
    <input type="text" id="username" name="username" autofocus /><input type="hidden" name="isbarcode" id="isbarcode" value="0" />
  </div>

  <div class="field">
    <label for="password">Passwort:</label>
    <input type="password" id="password" name="password" />
  </div>

  <div class="field">
    <label for="token">OTP (optional)</label>
    <input type="text" autocomplete="off" id="token" name="token" />
  </div>
  <!--<span id="loginmsg">[LOGINMSG]</span>-->
  <span style="color:red">[LOGINERRORMSG]</span>
  [STECHUHRDEVICE]
  <div class="field-row">
    [MULTIDB]
<!--    <div class="field">
      <select id="language" name="language">
        <option value="">- Sprache wählen -</option>
        <option [OPTIONLANGUAGEGERMAN] value="german">Deutsch</option>
        <option [OPTIONLANGUAGEENGLISH] value="englisch">English</option>
      </select>
    </div> -->
  </div>
  <div class="btn-wrapper field-row">
    <div class="field">
      <input type="submit" class="btn" value="Anmelden" />
    </div>
    <div class="field link">
      <a href="index.php?module=welcome&action=passwortvergessen">Passwort vergessen?</a>
    </div>
  </div>
</form>
