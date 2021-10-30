/******/ (() => { // webpackBootstrap
/******/ 	var __webpack_modules__ = ({

/***/ "./client/src/js/admintweaks.js":
/*!**************************************!*\
  !*** ./client/src/js/admintweaks.js ***!
  \**************************************/
/***/ (() => {

//console.log('LOADING');
(function ($) {
  //  console.log('RUNNING');
  //  window.ss = window.ss || {};
  $.entwine('ss', function ($) {
    $('.cms-sitename__version').entwine({
      onadd: function onadd(e) {
        $('body').addClass('fw_v' + this.text().split('.').shift()).addClass('fw_v' + this.text().replace('.', '-'));
      }
    }); // // adding flags to nested/complex fields if fluent is translating 'em
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
      onadd: function onadd(e) {
        // Crude... but this works to show a 'loading' overlay by adding the
        // 'data-show-loading-feedback' attribute to buttons
        var loading_feedback_progressbar_interval; //$('button[data-show-loading-feedback]dact').on('click',function(){

        $(this).on('click', function () {
          var stateClasses = 'loading show-loading-feedback';

          if ($(this).attr('data-show-loading-progressbar')) {
            stateClasses += ' show-loading-progressbar';
          } // add overlay (default CMS functionality)


          $('#pages-controller-cms-content').addClass(stateClasses); // optionally also add progress bar

          if ($(this).attr('data-show-loading-progressbar')) {
            $('#pages-controller-cms-content') //.hide().after(
            .append($('<div id="loading_feedback_progressbar">\n\
										<div class="progress_label">Working...</div></div>').progressbar({
              value: 0
            }).on("progressbarchange", function (event, ui) {
              var progr_bar = $(event.target);
              $('.progress_label', progr_bar).text("Working... " + progr_bar.progressbar("value") + "%");
            }).on("progressbarcomplete", function (event, ui) {
              $('.progress_label', progr_bar).text("Almost done. Please wait...");
            }) //.progressbar("value", 1) // trigger initial update
            );
            loading_feedback_progressbar_interval = window.setInterval(function () {
              // Just fake the progress for now...
              var add_progress = Math.round(Math.random() * 3);

              if (add_progress > 2) {
                $("#loading_feedback_progressbar").progressbar("value", $("#loading_feedback_progressbar").progressbar("value") + add_progress);
              }
            }, 1000);
          }
        }).on('remove', function () {
          //console.log('called');
          // hide CMS overlay
          if ($('#pages-controller-cms-content').hasClass('loading show-loading-feedback')) {
            $('#pages-controller-cms-content').removeClass('loading show-loading-feedback');
          } // clean up progress bar


          if (loading_feedback_progressbar_interval) window.clearInterval(loading_feedback_progressbar_interval);

          if ($('#pages-controller-cms-content').hasClass('show-loading-progressbar')) {
            $('#pages-controller-cms-content').removeClass('show-loading-progressbar');
            $("#loading_feedback_progressbar").progressbar('destroy').remove();
          }
        });
      } // end:onadd

    });
  }); // @TODO: check/fix this based on FUSE/DocSys?
  // Advanced search toggle for modeladmins

  $('#Form_SearchForm_q_Advanced').entwine({
    onmatch: function onmatch() {
      this._super();

      this.parents('.ModelAdmin').addClass('HasAdvancedSearch');
      this.checkState(); // apply Switchery

      new Switchery(this[0], {
        // color: '#3EBAE0',
        // color: '#55a4d2',
        color: '#338DC1',
        secondaryColor: '#D2D5D8',
        size: 'small'
      });
    },
    onchange: function onchange() {
      this._super();

      this.checkState();
    },
    checkState: function checkState() {
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

/***/ }),

/***/ "./client/src/scss/admintweaks.scss":
/*!******************************************!*\
  !*** ./client/src/scss/admintweaks.scss ***!
  \******************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
// extracted by mini-css-extract-plugin


/***/ })

/******/ 	});
/************************************************************************/
/******/ 	// The module cache
/******/ 	var __webpack_module_cache__ = {};
/******/ 	
/******/ 	// The require function
/******/ 	function __webpack_require__(moduleId) {
/******/ 		// Check if module is in cache
/******/ 		var cachedModule = __webpack_module_cache__[moduleId];
/******/ 		if (cachedModule !== undefined) {
/******/ 			return cachedModule.exports;
/******/ 		}
/******/ 		// Create a new module (and put it into the cache)
/******/ 		var module = __webpack_module_cache__[moduleId] = {
/******/ 			// no module.id needed
/******/ 			// no module.loaded needed
/******/ 			exports: {}
/******/ 		};
/******/ 	
/******/ 		// Execute the module function
/******/ 		__webpack_modules__[moduleId](module, module.exports, __webpack_require__);
/******/ 	
/******/ 		// Return the exports of the module
/******/ 		return module.exports;
/******/ 	}
/******/ 	
/******/ 	// expose the modules object (__webpack_modules__)
/******/ 	__webpack_require__.m = __webpack_modules__;
/******/ 	
/************************************************************************/
/******/ 	/* webpack/runtime/chunk loaded */
/******/ 	(() => {
/******/ 		var deferred = [];
/******/ 		__webpack_require__.O = (result, chunkIds, fn, priority) => {
/******/ 			if(chunkIds) {
/******/ 				priority = priority || 0;
/******/ 				for(var i = deferred.length; i > 0 && deferred[i - 1][2] > priority; i--) deferred[i] = deferred[i - 1];
/******/ 				deferred[i] = [chunkIds, fn, priority];
/******/ 				return;
/******/ 			}
/******/ 			var notFulfilled = Infinity;
/******/ 			for (var i = 0; i < deferred.length; i++) {
/******/ 				var [chunkIds, fn, priority] = deferred[i];
/******/ 				var fulfilled = true;
/******/ 				for (var j = 0; j < chunkIds.length; j++) {
/******/ 					if ((priority & 1 === 0 || notFulfilled >= priority) && Object.keys(__webpack_require__.O).every((key) => (__webpack_require__.O[key](chunkIds[j])))) {
/******/ 						chunkIds.splice(j--, 1);
/******/ 					} else {
/******/ 						fulfilled = false;
/******/ 						if(priority < notFulfilled) notFulfilled = priority;
/******/ 					}
/******/ 				}
/******/ 				if(fulfilled) {
/******/ 					deferred.splice(i--, 1)
/******/ 					var r = fn();
/******/ 					if (r !== undefined) result = r;
/******/ 				}
/******/ 			}
/******/ 			return result;
/******/ 		};
/******/ 	})();
/******/ 	
/******/ 	/* webpack/runtime/hasOwnProperty shorthand */
/******/ 	(() => {
/******/ 		__webpack_require__.o = (obj, prop) => (Object.prototype.hasOwnProperty.call(obj, prop))
/******/ 	})();
/******/ 	
/******/ 	/* webpack/runtime/make namespace object */
/******/ 	(() => {
/******/ 		// define __esModule on exports
/******/ 		__webpack_require__.r = (exports) => {
/******/ 			if(typeof Symbol !== 'undefined' && Symbol.toStringTag) {
/******/ 				Object.defineProperty(exports, Symbol.toStringTag, { value: 'Module' });
/******/ 			}
/******/ 			Object.defineProperty(exports, '__esModule', { value: true });
/******/ 		};
/******/ 	})();
/******/ 	
/******/ 	/* webpack/runtime/jsonp chunk loading */
/******/ 	(() => {
/******/ 		// no baseURI
/******/ 		
/******/ 		// object to store loaded and loading chunks
/******/ 		// undefined = chunk not loaded, null = chunk preloaded/prefetched
/******/ 		// [resolve, reject, Promise] = chunk loading, 0 = chunk loaded
/******/ 		var installedChunks = {
/******/ 			"/js/admintweaks": 0,
/******/ 			"css/admintweaks": 0
/******/ 		};
/******/ 		
/******/ 		// no chunk on demand loading
/******/ 		
/******/ 		// no prefetching
/******/ 		
/******/ 		// no preloaded
/******/ 		
/******/ 		// no HMR
/******/ 		
/******/ 		// no HMR manifest
/******/ 		
/******/ 		__webpack_require__.O.j = (chunkId) => (installedChunks[chunkId] === 0);
/******/ 		
/******/ 		// install a JSONP callback for chunk loading
/******/ 		var webpackJsonpCallback = (parentChunkLoadingFunction, data) => {
/******/ 			var [chunkIds, moreModules, runtime] = data;
/******/ 			// add "moreModules" to the modules object,
/******/ 			// then flag all "chunkIds" as loaded and fire callback
/******/ 			var moduleId, chunkId, i = 0;
/******/ 			if(chunkIds.some((id) => (installedChunks[id] !== 0))) {
/******/ 				for(moduleId in moreModules) {
/******/ 					if(__webpack_require__.o(moreModules, moduleId)) {
/******/ 						__webpack_require__.m[moduleId] = moreModules[moduleId];
/******/ 					}
/******/ 				}
/******/ 				if(runtime) var result = runtime(__webpack_require__);
/******/ 			}
/******/ 			if(parentChunkLoadingFunction) parentChunkLoadingFunction(data);
/******/ 			for(;i < chunkIds.length; i++) {
/******/ 				chunkId = chunkIds[i];
/******/ 				if(__webpack_require__.o(installedChunks, chunkId) && installedChunks[chunkId]) {
/******/ 					installedChunks[chunkId][0]();
/******/ 				}
/******/ 				installedChunks[chunkIds[i]] = 0;
/******/ 			}
/******/ 			return __webpack_require__.O(result);
/******/ 		}
/******/ 		
/******/ 		var chunkLoadingGlobal = self["webpackChunkbase_package_json"] = self["webpackChunkbase_package_json"] || [];
/******/ 		chunkLoadingGlobal.forEach(webpackJsonpCallback.bind(null, 0));
/******/ 		chunkLoadingGlobal.push = webpackJsonpCallback.bind(null, chunkLoadingGlobal.push.bind(chunkLoadingGlobal));
/******/ 	})();
/******/ 	
/************************************************************************/
/******/ 	
/******/ 	// startup
/******/ 	// Load entry module and return exports
/******/ 	// This entry module depends on other loaded chunks and execution need to be delayed
/******/ 	__webpack_require__.O(undefined, ["css/admintweaks"], () => (__webpack_require__("./client/src/js/admintweaks.js")))
/******/ 	var __webpack_exports__ = __webpack_require__.O(undefined, ["css/admintweaks"], () => (__webpack_require__("./client/src/scss/admintweaks.scss")))
/******/ 	__webpack_exports__ = __webpack_require__.O(__webpack_exports__);
/******/ 	
/******/ })()
;