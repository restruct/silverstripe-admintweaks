(()=>{var e,s={251:()=>{var e;(e=jQuery).entwine("ss",(function(e){e(".cms-sitename__version").entwine({onadd:function(s){e("body").addClass("fw_v"+this.text().split(".").shift()).addClass("fw_v"+this.text().replace(".","-"))}}),e("input.styled-checkbox").entwine({onadd:function(s){this.hasClass("styled-checkbox-hide")||(this.addClass("styled-checkbox-hide").after(e("<label></label>").attr({for:this.attr("id")})),this._super())}}),e("[data-setactivecheckboxvalues]").entwine({onmatch:function(e){this.find('[value="'+this.data("setactivecheckboxvalues").join('"], [value="')+'"]').prop("checked",!0)}}),e('.ss-gridfield-item input[type="checkbox"]').entwine({onchange:function(s){this.prop("checked")?this.siblings(".checkbox_zero_input").remove():e("<input>").attr({class:"checkbox_zero_input",type:"hidden",name:this.attr("name")}).insertAfter(this),this._super()}}),e("button[data-show-loading-feedback]").entwine({onadd:function(s){var r;e(this).on("click",(function(){var s="loading show-loading-feedback";e(this).attr("data-show-loading-progressbar")&&(s+=" show-loading-progressbar"),e("#pages-controller-cms-content").addClass(s),e(this).attr("data-show-loading-progressbar")&&(e("#pages-controller-cms-content").append(e('<div id="loading_feedback_progressbar">\n\t\t\t\t\t\t\t\t\t\t<div class="progress_label">Working...</div></div>').progressbar({value:0}).on("progressbarchange",(function(s,r){var a=e(s.target);e(".progress_label",a).text("Working... "+a.progressbar("value")+"%")})).on("progressbarcomplete",(function(s,r){e(".progress_label",progr_bar).text("Almost done. Please wait...")}))),r=window.setInterval((function(){var s=Math.round(3*Math.random());s>2&&e("#loading_feedback_progressbar").progressbar("value",e("#loading_feedback_progressbar").progressbar("value")+s)}),1e3))})).on("remove",(function(){e("#pages-controller-cms-content").hasClass("loading show-loading-feedback")&&e("#pages-controller-cms-content").removeClass("loading show-loading-feedback"),r&&window.clearInterval(r),e("#pages-controller-cms-content").hasClass("show-loading-progressbar")&&(e("#pages-controller-cms-content").removeClass("show-loading-progressbar"),e("#loading_feedback_progressbar").progressbar("destroy").remove())}))}})})),e("#Form_SearchForm_q_Advanced").entwine({onmatch:function(){this._super(),this.parents(".ModelAdmin").addClass("HasAdvancedSearch"),this.checkState(),new Switchery(this[0],{color:"#338DC1",secondaryColor:"#D2D5D8",size:"small"})},onchange:function(){this._super(),this.checkState()},checkState:function(){this.prop("checked")?e("#Form_SearchForm").addClass("show_advanced_searchfields"):e("#Form_SearchForm").removeClass("show_advanced_searchfields")}})},359:()=>{}},r={};function a(e){var t=r[e];if(void 0!==t)return t.exports;var o=r[e]={exports:{}};return s[e](o,o.exports,a),o.exports}a.m=s,e=[],a.O=(s,r,t,o)=>{if(!r){var n=1/0;for(d=0;d<e.length;d++){for(var[r,t,o]=e[d],i=!0,c=0;c<r.length;c++)(!1&o||n>=o)&&Object.keys(a.O).every((e=>a.O[e](r[c])))?r.splice(c--,1):(i=!1,o<n&&(n=o));if(i){e.splice(d--,1);var l=t();void 0!==l&&(s=l)}}return s}o=o||0;for(var d=e.length;d>0&&e[d-1][2]>o;d--)e[d]=e[d-1];e[d]=[r,t,o]},a.o=(e,s)=>Object.prototype.hasOwnProperty.call(e,s),(()=>{var e={593:0,753:0};a.O.j=s=>0===e[s];var s=(s,r)=>{var t,o,[n,i,c]=r,l=0;if(n.some((s=>0!==e[s]))){for(t in i)a.o(i,t)&&(a.m[t]=i[t]);if(c)var d=c(a)}for(s&&s(r);l<n.length;l++)o=n[l],a.o(e,o)&&e[o]&&e[o][0](),e[o]=0;return a.O(d)},r=self.webpackChunkrestruct_silverstripe_admintweaks=self.webpackChunkrestruct_silverstripe_admintweaks||[];r.forEach(s.bind(null,0)),r.push=s.bind(null,r.push.bind(r))})(),a.O(void 0,[753],(()=>a(251)));var t=a.O(void 0,[753],(()=>a(359)));t=a.O(t)})();