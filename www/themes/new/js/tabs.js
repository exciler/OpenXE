/* initialize tabs */
$( "#tabs" ).tabs();

document.querySelectorAll('#tabs ul.ui-tabs-nav li a').forEach((item) => {
    if(item.textContent === '')
    {
        const parent = item.parentElement;
        parent.classList.toggle('active', false);
        parent.style.background = 'transparent';
        parent.style.backgroundColor = 'transparent';
    }
});
$('#tabs').find('div.ui-tabs-panel:visible table.defferloading').each(function () {
    $(this).toggleClass('defferloading', false);
    $(this).dataTable().fnSettings().sAjaxSource = $(this).dataTable().fnSettings().sAjaxSource.replace('&deferLoading=1','');
    $(this).DataTable().ajax.reload();
});

$('#tabs').on('tabsactivate', function( event, ui ) {
    $('#tabs').find('div.ui-tabs-panel:visible table.defferloading').each(function(){
        $(this).toggleClass('defferloading', false);
        $(this).dataTable().fnSettings().sAjaxSource = $(this).dataTable().fnSettings().sAjaxSource.replace('&deferLoading=1','');
        $(this).DataTable().ajax.reload();
    });
});
