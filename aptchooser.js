$('.progress').hide();
$('.message').hide();
$('#SecondForm').hide();
$.ajax({
    url: 'data.json',
    type: 'get',
    datatype: 'json',
    success: function(data){
        var versi = '';
    $.each(data.dist,function(i,isi){
    versi += '<option value="'+i+'">'+isi.nama+'</option>';
    });
    $('select[name="dist"]').append(versi);
	var mirror = '';
    $.each(data.mirror,function(i,name){
    mirror += '<option value="'+i+'">'+name.nama+'</option>';
    });
    $('select[name="mirror"]').append(mirror);
    }
});
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
	if($('.message').hasClass('alert-danger')){
	    $('.message').removeClass('alert-danger').addClass('alert-warning');
	}
        $('.message').show(800);
        $('.message').text('Please wait');

        var maindata = $('form#MainForm').serialize();
	$.ajax({
        url: "process.php",
        type: "GET",
        data: maindata,
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
$('input[name="package"]')
        .popover({ html: 'true', placement: 'bottom', title: 'Nama paket', content: "Ini adalah nama paket yang ada dalam database APT, seperti misalnya <strong>chromium-browser</strong>, <strong>smplayer</strong>, dsb.", container: 'body' })
        .blur(function () {
            $(this).popover('hide');
});
$('.apt-web').click(function(event){
    event.preventDefault();
    $('#MainForm').slideToggle();
    $('#SecondForm').slideToggle('slow');
});
$('input[name="packages"]')
    .popover({ html: 'true', placement: 'bottom', title: 'Nama paket', content: "Ini adalah nama paket yang ada dalam database APT, seperti misalnya <strong>chromium-browser</strong>, <strong>smplayer</strong>, dsb.<br/>Anda bisa menambah parameter:<br/> <code>--no-install-recommends<code>" });
$('select')
	.popover({ placement: 'right', title: 'Pilih dulu', content: "Silakan pilih salah satu opsi", container: 'body'}).click(function() {
            $(this).popover('hide');
});
$('button[name="submit"]').click(function(ev){
    ev.preventDefault();
    
    if($('input[name="packages"]').val().length == 0)
    {
        $('.message').show();
        $('.message').text("Package name cannot be left empty.");
        $('.message').delay(5000).slideUp(800);
    }
    else if($('select[name="dist"]').val().length == 0)
    {
	$('select[name="dist"]').popover('show');
    }
    else if(!$('select[name="mirror"]').val())
    {
	$('select[name="mirror"]').popover('show');
    }
    else if(!$('.message').hasClass('alert-danger'))
    {
	if(!$('.status').hasClass('alert-success') || !$('.status').hasClass('alert-info')){
	    $('.status').removeClass('hidden').addClass('alert-info').show();
	    $('.status').text('Memeriksa aktivitas server');
	}
	if(!$('.results').is(':empty'))
	{
	    $('.results').text('');
	}
        $('.progress').show();
        $('.progress-bar').attr('style','width:50%;');
        $('.message').show(800);
        $('.message').text('Mohon tunggu sebentar');

        var datadata = $('form#SecondForm').serialize();
	$.ajax({
        url: "process.php",
        type: "GET",
        data: datadata,
        success: function(data){
	    if(data.length <= 1){
		$('.message').addClass('alert-danger');
		$('.message').text('Maaf, server tidak sedang aktif untuk saat ini. Cobalah lagi lain kali.');
		$('.progress-bar').attr('style','width:100%;');
		$('.status').slideUp('fast');
		$('.status').removeClass('alert-info');
	    }
	    else
	    {
		$('.results').html(data);
		$('.progress-bar').attr('style','width:100%;');
		$('.progress').slideUp(1000);
		$('.message').slideUp(800);
		$('.status').removeClass('alert-info').addClass('alert-success').text('Server sedang aktif.');
	    }
	},
        error: function(msg) {
            //alert(msg);
	    return;
	    }
	});

    }
    else
    {
	return;
    }
    
});
