function Counter() {
    this.count = 0;
    this.total = 0;
}

Counter.prototype = {
    setTotal: function(total) {
        if(this.total < total)
            this.total = total;
    },
    upCount: function(count) {
        this.count += count;
    },
    setValues: function(values) {
        this.values = values;
    }
};

$(document).ready(function(){
    $(document).on('submit', 'form', function(e) {
        var formData = new FormData(),
            omega = $('#omega')[0].files[0],
            continental = $('#continental')[0].files[0],
            countObject = new Counter();

        if(omega || continental)
            e.preventDefault();
        else
            return true;

        if(typeof omega !== 'undefined')
            formData.append('omega', omega);
        if(typeof continental !== 'undefined')
            formData.append('continental', continental);

        importFile(formData, countObject);
    });


    function importFile(formData, object) {
        if(typeof object.values !== 'undefined')
            formData.append('values', JSON.stringify(object.values));

        $.ajax({
            url : '/modules/ajaximport/ajaximport-ajax.php',
            type : 'POST',
            data : formData,
            dataType: 'json',
            processData: false,
            contentType: false,
            success : function(data) {
                object.setTotal(data.total);
                object.setValues(data.values);
                object.upCount(data.count);

                $('#progress').prepend(data.message);
                $('#progressbar').attr('max', object.total);
                $('#progressbar').val(object.count);

                if(Object.keys(object.values).length)
                    importFile(formData, object);
            }
        });

        return object;
    }
});