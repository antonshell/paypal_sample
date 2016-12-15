function getNewInsertion(oldValue, newValue, newPosition) {

    var type='';
    var from=0;
    var length=0;
    var text='';

    if(oldValue.length > newValue.length) {
        type = 'deletion';
        changeLength = oldValue.length - newValue.length;
        pos = newPosition;
        text = oldValue.substr(pos, changeLength);
    } else {
        type = 'insertion';
        changeLength = newValue.length - oldValue.length;
        pos = newPosition - changeLength;
        text = newValue.substr(pos, changeLength);
    }

    onTextChanged.onChange(onTextChanged.id);

    return {text:text, type:type, position: pos, changeLength: changeLength};
}

$(function() {
    var input = $(onTextChanged.id);

    input.data("old_value", input.val());

    input.bind("paste cut keydown",function(e) {
        var that = this;
        setTimeout(function() {
            if(typeof $(that).data("old_value") == "undefined") {
                $(that).data("old_value",$(that).val());
            }

            getNewInsertion($(that).data("old_value"), $(that).val(), e.target.selectionStart);
            $(that).data("old_value",$(that).val());
        },200);

    })

});