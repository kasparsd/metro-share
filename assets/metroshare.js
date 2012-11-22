jQuery(window).ready(function($) {
	$('.metroshare').fadeIn();

	$('.metroshare .metro-tabs a').click(function() {
		$( $(this).attr('href') ).submit();
		return false;
	})

	$('.metroshare form').submit(function(){
		window.open('', 'formpopup', 'menubar=no,toolbar=no,resizable=yes,scrollbars=yes,height=400,width=600');
        this.target = 'formpopup';
	});
});