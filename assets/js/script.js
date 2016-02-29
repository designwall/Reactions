jQuery(document).ready(function($){
	$('div.dw-reactions').mouseenter(function(e){
		$(this).addClass('reaction-show');
	});

	$('div.dw-reactions').mouseleave(function(e){
		$(this).removeClass('reaction-show');
	});

	$('.reaction').on('click', function(e){
		e.preventDefault();

		var t = $(this), $class = $(this).attr('class'), parent = t.parent();
		res = $class.split(' ');
		type = res[1].split('-');

		$.ajax({
			url: dw_reaction.ajax,
			dataType: 'json',
			type: 'POST',
			data: {
				action: 'dw_reaction_save_action',
				nonce: parent.data('nonce'),
				type: type[1],
				post: parent.data('post')
			},
			success: function(data) {
				console.log(data);
			}
		});
	})
});