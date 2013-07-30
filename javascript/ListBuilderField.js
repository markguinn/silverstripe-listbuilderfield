/**
 * @author Mark Guinn <mark@adaircreative.com>
 * @package listbuilderfield
 * @date 06.09.2013
 */
(function ($, window, document, undefined) {
	'use strict';

	$('.field.listbuilder').each(function(){
		var field   = this,
			fieldID = this.id;

		$('.masterList', this).sortable({
			connectWith:    '#' + fieldID + ' .selectedList'
		}).disableSelection();

		$('.selectedList', this).sortable({
			connectWith:    '#' + fieldID + ' .masterList',
			update:function(event, ui) {
				var idList = [];

				$(this).children().each(function(index, element){
					idList.push(this.getAttribute('data-id'));
				});

				$('input[type=hidden]', field).val(idList.join(','));
			}
		}).disableSelection();
	});
}(jQuery, this, this.document));