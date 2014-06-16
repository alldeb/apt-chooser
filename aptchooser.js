$('.progress').hide();
$('.message').hide();
$('button[name="find"]').click(function(ev){
    ev.preventDefault();
    if(!$('.results').is(':empty'))
    {
        $('.results').text('');
    }
    if($('input[name="package"]').val().length == 0)
    {
        $('.message').show();
        $('.message').text("Package name cannot be left empty.");
        $('.message').delay(5000).slideUp(800);
    }
    else
    {
        $('.progress').show();
        $('.progress-bar').attr('style','width:50%;');
        $('.message').show(800);
        $('.message').text('Please wait');

        var datadata = $('form#MainForm').serialize();
    $.ajax({
        url: "process.php",
        type: "GET",
        data: datadata,
        success: function(data){
            $('.results').html(data);
            $('.progress-bar').attr('style','width:100%;');
            $('.progress').slideUp(1000);
            $('.message').slideUp(800);
        },
        error: function(msg) {
            alert(msg);
        }
    });
    }
});
$('button.btn-link').click(function(ev){
    ev.preventDefault();
    $('#sourceCode').modal('show');
    if($('pre.prettyprint').is(':empty'))
    {
        $('pre.prettyprint').text("Please wait");
    $.ajax({
        url: "process.php",
        type: "GET",
        data: { source: "show"},
        success: function(data){
            $('pre.prettyprint').text(data);
        }
    });
    }
});
$('a.gplLicense').click(function(ev){
    ev.preventDefault();
    $('#license').modal('show');
    if($('pre.gpl').is(':empty'))
    {
        $('pre.gpl').text("Please wait");
    $.ajax({
        url: "gpl.txt",
        type: "GET",
        success: function(data){
            $('pre.gpl').text(data);
        }
    });
    }
});
$('button[name="parse"]').click(function(ev){
    ev.preventDefault();
    if(!$('.adminResults p').is(':empty'))
    {
        $('.adminResults p').removeClass('alert-warning alert-success');
        $('.adminResults p').text('');
    }

    if(!$('input[name="pass"]').val())
    {
        $('.adminResults p').addClass('alert-warning').text("Please provide the right password.");
    }
    else if(!$('input[name="file"]').val())
    {
        $('.adminResults p').addClass('alert-warning').text("Please input file name.");
    }
    else if(!$('input[name="repo"]').val())
    {
        $('.adminResults p').addClass('alert-warning').text("Please give a repo name.");
    }
    else if(!$('input[name="component"]').val())
    {
        $('.adminResults p').addClass('alert-warning').text("Please select a component name.");
    }
    else if(!$('input[name="arch"]').val())
    {
        $('.adminResults p').addClass('alert-warning').text("Please provide an arch type.");
    }
    else
    {
        $('.adminResults p').text('Please wait');

        var datadata = $('form#admin').serialize();
    $.ajax({
        url: "process.php",
        type: "POST",
        data: datadata,
        success: function(data){
            $('.adminResults p').addClass('alert-success').text(data);
        },
        error: function(msg) {
            alert(msg);
        }
    });
    }
});