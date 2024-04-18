

const username = document.querySelector('#username');
const isBarcode = document.querySelector('#isbarcode');
isBarcode.value = 0;
username.addEventListener('keydown', (evt) => {
    const pos = username.value.indexOf('!!!')
    if (evt.key === "Enter") {
        evt.preventDefault();
        if (pos < 0)
            document.querySelector('#password').focus();
        else
            document.querySelector('#frmlogin').submit();
    } else if (username.value.indexOf('!!!') > 0) {
        document.querySelector('#password').focus();
        username.value = username.value.substring(0, pos);
        isBarcode.value = 1;
    }
})

function checkindexdb()
{
    document.querySelector('#stechuhrdevice').style.visibility = 'hidden';
    if (typeof indexedDB === undefined)
        return;

    const request = indexedDB.open('wawisionstechuhrdevice', 1);
    request.addEventListener('upgradeneeded', (event) => {
        const db = event.target.result;
        if (!db.objectStoreNames.contains('stechuhr')) {
            const store = db.createObjectStore('stechuhr', {
                keyPath: 'key',
                autoIncrement: true
            });
        }
    });

    request.addEventListener('success', (event) => {
        const db = request.result;
        const trans = db.transaction(['stechuhr'], 'readonly');
        const store = trans.objectStore('stechuhr');

        const gefunden = false;
        const range = IDBKeyRange.lowerBound(0);
        const cursorRequest = store.openCursor(range);

        // Wird für jeden gefundenen Datensatz aufgerufen... und einmal extra
        cursorRequest.addEventListener('success', (event) => {
            const result = event.target.result;
            if (result && typeof result.value !== undefined
                && typeof result.value.code !== undefined && result.value.code !== '') {
                document.querySelector('#stechuhrdevice').style.visibility = 'visible';
                if (typeof (Storage) !== undefined) {
                    localStorage.setItem("devicecode", result.value.code);
                }
                checkdevicecode(result.value.code);
            }
        });
    });
}

function checkdevicecode(devicecode)
{
    const stechuhrdevice = document.querySelector('#stechuhrdevice');
    stechuhrdevice.each(function(){
        $('#token').parent().hide();
        $('#password').parent().hide();
        $('#username').parent().hide();
        $('#loginmsg').hide();
        $('#chtype').show();
        $('#chtype').on('click',function()
        {
            $('#token').parent().show();
            $('#password').parent().show();
            $('#username').parent().show();
            $('#loginmsg').show();
            $(this).hide();
            clearInterval(siv);
        });
        $('#code').val(devicecode);
        $('#stechuhrdevice').focus();
        $( "#stechuhrdevice" ).on('keydown',function( event ) {
            setTimeout(function(){
                if($('#stechuhrdevice').val().length > 205)
                    setTimeout(function(){$('#frmlogin').submit();},100);
            }, 500);

        });
        siv = setInterval(function(){$('#stechuhrdevice').focus(),200});
    });
    $('#rfid').each(function(){
        $('#code').val(devicecode);
        $('#token').parent().hide();
        $('#password').parent().hide();
        $('#username').parent().hide();
        $('#loginmsg').hide();
        $('#chtype').show();
        $('#chtype').on('click',function()
        {
            $('#token').parent().show();
            $('#password').parent().show();
            $('#username').parent().show();
            $('#loginmsg').show();
            $(this).hide();
            clearInterval(siv);
        });

        intv = setInterval(function()
        {
            checkrf(devicecode);
        },1000);
    });
}

function checkrf(devicecode)
{
    clearInterval(intv);
    intv = setInterval(function()
    {
        checkrf(devicecode);
    },3000);
    $.ajax({
        url: 'index.php?module=welcome&action=login&cmd=checkrfid',
        type: 'POST',
        dataType: 'json',
        data: {code: devicecode},
        success: function(data) {
            if(typeof data.rfidcode != 'undefined' && data.rfidcode != '')
            {
                clearInterval(intv);
                if(typeof data.code != 'undefined')$('#code').val(data.code);
                $('#rfidcode').val(data.rfidcode);
                $('#frmlogin').submit();
            }else{
                if(typeof data.rfidcode != 'undefined')
                {
                    clearInterval(intv);
                    intv = setInterval(function()
                    {
                        checkrf(devicecode);
                    },1000);
                }
            }
        }
    });
}
