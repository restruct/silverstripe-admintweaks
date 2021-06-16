//console.log('LOADING');

(function($) {

//  console.log('RUNNING');

//  window.ss = window.ss || {};

  $.entwine('ss', function ($) {

    $('.cms-sitename__version').entwine({
      onadd: function (e) {
        $('body')
          .addClass('fw_v' + this.text().split('.').shift())
          .addClass('fw_v' + this.text().replace('.','-'));
      }
    });

    // // adding flags to nested/complex fields if fluent is translating 'em
    // $('.field.fieldgroup:not(.LocalisedField) input.LocalisedField').entwine({
    //   onadd: function (e) {
    //     //console.log(this.val());
    //     this.prev('label').addClass('LocalisedField');
    //     //this.append('<span class="ui-button-icon-primary ui-icon btn-icon-cross-circle"></span>');
    //   }
    // });

    // // insert labels after gridfield checkboxes
    // $('.ss-gridfield-item input.styled-checkbox').entwine({
    //   onadd: function (e) {
    //     if (this.hasClass('styled-checkbox-hide')) return;
    //     this.addClass('styled-checkbox-hide').after($('<label></label>').attr({for: this.attr('id')}));
    //     this._super();
    //   }
    // });
    //
    // // missing button icons in gridfield... workaround
    // //	$('button.gridfield-button-delete').entwine({
    // //		onadd: function(e){
    // //			this.append('<span class="ui-button-icon-primary ui-icon btn-icon-cross-circle"></span>');
    // //		}
    // //	})
    // //	$('button.gridfield-button-unlink').entwine({
    // //		onadd: function(e){
    // //			this.append('<span class="ui-button-icon-primary ui-icon btn-icon-chain--minus"></span>');
    // //		}
    // //	})

    // @TODO: fix this based on HLCL publisher update publications action
    // Add optional loading feedback overlay to buttons
    $('button[data-show-loading-feedback]').entwine({

      // set eventlisteners from onadd because I have no clue how to override/subclass
      // the default eventhandlers set from the CMS's entwine...
      onadd: function (e) {

        // Crude... but this works to show a 'loading' overlay by adding the
        // 'data-show-loading-feedback' attribute to buttons
        var loading_feedback_progressbar_interval;
        //$('button[data-show-loading-feedback]dact').on('click',function(){
        $(this).on('click', function () {
          var stateClasses = 'loading show-loading-feedback';
          if ($(this).attr('data-show-loading-progressbar')) {
            stateClasses += ' show-loading-progressbar';
          }
          // add overlay (default CMS functionality)
          $('#pages-controller-cms-content').addClass(stateClasses);
          // optionally also add progress bar
          if ($(this).attr('data-show-loading-progressbar')) {
            $('#pages-controller-cms-content')
            //.hide().after(
              .append(
                $('<div id="loading_feedback_progressbar">\n\
										<div class="progress_label">Working...</div></div>')
                  .progressbar({
                    value: 0
                  }).on("progressbarchange", function (event, ui) {
                  var progr_bar = $(event.target);
                  $('.progress_label', progr_bar)
                    .text("Working... " + progr_bar.progressbar("value") + "%");
                }).on("progressbarcomplete", function (event, ui) {
                  $('.progress_label', progr_bar)
                    .text("Almost done. Please wait...");
                }) //.progressbar("value", 1) // trigger initial update
              );
            loading_feedback_progressbar_interval = window.setInterval(function () {
              // Just fake the progress for now...
              var add_progress = Math.round(Math.random() * 3);
              if (add_progress > 2) {
                $("#loading_feedback_progressbar").progressbar("value",
                  $("#loading_feedback_progressbar").progressbar("value") + add_progress
                );
              }
            }, 1000);
          }
        }).on('remove', function () {
          //console.log('called');
          // hide CMS overlay
          if ($('#pages-controller-cms-content').hasClass('loading show-loading-feedback')) {
            $('#pages-controller-cms-content').removeClass('loading show-loading-feedback');
          }
          // clean up progress bar
          if (loading_feedback_progressbar_interval) window.clearInterval(loading_feedback_progressbar_interval);
          if ($('#pages-controller-cms-content').hasClass('show-loading-progressbar')) {
            $('#pages-controller-cms-content').removeClass('show-loading-progressbar');
            $("#loading_feedback_progressbar").progressbar('destroy').remove();
          }
        });

      } // end:onadd

    });

  });

  // @TODO: check/fix this based on FUSE/DocSys?
  // Advanced search toggle for modeladmins
  $('#Form_SearchForm_q_Advanced').entwine({
    onmatch: function () {
      this._super();
      this.parents('.ModelAdmin').addClass('HasAdvancedSearch');
      this.checkState();
      // apply Switchery
      new Switchery(this[0], {
        // color: '#3EBAE0',
        // color: '#55a4d2',
        color: '#338DC1',
        secondaryColor: '#D2D5D8',
        size: 'small'
      });
    },
    onchange: function () {
      this._super();
      this.checkState();
    },
    checkState: function () {
      // console.log('called', this.prop('checked'));
      if (this.prop('checked')) {
        $('#Form_SearchForm').addClass('show_advanced_searchfields');
      } else {
        $('#Form_SearchForm').removeClass('show_advanced_searchfields');
      }
    }
  });


  /**
   * Inline gridfieldeditablecolumns datepicker fixes
   */
//		$(document).on('click', '.field.ss-gridfield-editable input.text.date', function() {
//			console.log('clicked');
////		$('.field.ss-gridfield-editable input.text.date').entwine({
////			onclick: function(e) {
//				// set unique IDs on datafields which haven't got one yet
//				// (required for datefield to update the correct field):
////				$('input.text.date').each(function(){
////					$(this).attr('id', 'ID'+Math.floor((Math.random() * 10000) + 1));
////					$(this).attr('data-id',$(this).attr('id'));
////				});
//				// init datepicker
//				$(this).ssDatepicker();
////				if($(this).data('datepicker')) {
//					$(this).datepicker('show');
////				}
////			}
//		});

})(jQuery);