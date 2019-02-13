

function markerrors() {
    var errorstr;

    for (fid in formerrors) {
        $('#' + fid).addClass('error');

        errorstr = '<div style="display:inline-block"><span class="error">' + formerrors[fid] + '</span>';

        $('#' + fid).before(errorstr);
        $('#' + fid).after('</div>');
    }

}