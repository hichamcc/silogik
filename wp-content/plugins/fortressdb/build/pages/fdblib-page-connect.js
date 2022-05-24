/*! For license information please see fdblib-page-connect.js.LICENSE.txt */
!function(e,t){"object"==typeof exports&&"object"==typeof module?module.exports=t(require("FDBLib").Components):"function"==typeof define&&define.amd?define("FDBLib",[],t):"object"==typeof exports?exports.FDBLib=t(require("FDBLib").Components):(e.FDBLib=e.FDBLib||{},e.FDBLib.Pages=t(e.FDBLib.Components))}(self,(function(e){return(()=>{var t={184:(e,t)=>{var n;!function(){"use strict";var r={}.hasOwnProperty;function o(){for(var e=[],t=0;t<arguments.length;t++){var n=arguments[t];if(n){var a=typeof n;if("string"===a||"number"===a)e.push(n);else if(Array.isArray(n)){if(n.length){var i=o.apply(null,n);i&&e.push(i)}}else if("object"===a)if(n.toString===Object.prototype.toString)for(var c in n)r.call(n,c)&&n[c]&&e.push(c);else e.push(n.toString())}}return e.join(" ")}e.exports?(o.default=o,e.exports=o):void 0===(n=function(){return o}.apply(t,[]))||(e.exports=n)}()},705:(e,t,n)=>{var r=n(639).Symbol;e.exports=r},932:e=>{e.exports=function(e,t){for(var n=-1,r=null==e?0:e.length,o=Array(r);++n<r;)o[n]=t(e[n],n,e);return o}},663:e=>{e.exports=function(e,t,n,r){var o=-1,a=null==e?0:e.length;for(r&&a&&(n=e[++o]);++o<a;)n=t(n,e[o],o,e);return n}},286:e=>{e.exports=function(e){return e.split("")}},865:e=>{var t=/[^\x00-\x2f\x3a-\x40\x5b-\x60\x7b-\x7f]+/g;e.exports=function(e){return e.match(t)||[]}},239:(e,t,n)=>{var r=n(705),o=n(607),a=n(333),i=r?r.toStringTag:void 0;e.exports=function(e){return null==e?void 0===e?"[object Undefined]":"[object Null]":i&&i in Object(e)?o(e):a(e)}},674:e=>{e.exports=function(e){return function(t){return null==e?void 0:e[t]}}},259:e=>{e.exports=function(e,t,n){var r=-1,o=e.length;t<0&&(t=-t>o?0:o+t),(n=n>o?o:n)<0&&(n+=o),o=t>n?0:n-t>>>0,t>>>=0;for(var a=Array(o);++r<o;)a[r]=e[r+t];return a}},531:(e,t,n)=>{var r=n(705),o=n(932),a=n(469),i=n(448),c=r?r.prototype:void 0,u=c?c.toString:void 0;e.exports=function e(t){if("string"==typeof t)return t;if(a(t))return o(t,e)+"";if(i(t))return u?u.call(t):"";var n=t+"";return"0"==n&&1/t==-1/0?"-0":n}},180:(e,t,n)=>{var r=n(259);e.exports=function(e,t,n){var o=e.length;return n=void 0===n?o:n,!t&&n>=o?e:r(e,t,n)}},805:(e,t,n)=>{var r=n(180),o=n(689),a=n(140),i=n(833);e.exports=function(e){return function(t){t=i(t);var n=o(t)?a(t):void 0,c=n?n[0]:t.charAt(0),u=n?r(n,1).join(""):t.slice(1);return c[e]()+u}}},393:(e,t,n)=>{var r=n(663),o=n(816),a=n(748),i=RegExp("['’]","g");e.exports=function(e){return function(t){return r(a(o(t).replace(i,"")),e,"")}}},389:(e,t,n)=>{var r=n(674)({À:"A",Á:"A",Â:"A",Ã:"A",Ä:"A",Å:"A",à:"a",á:"a",â:"a",ã:"a",ä:"a",å:"a",Ç:"C",ç:"c",Ð:"D",ð:"d",È:"E",É:"E",Ê:"E",Ë:"E",è:"e",é:"e",ê:"e",ë:"e",Ì:"I",Í:"I",Î:"I",Ï:"I",ì:"i",í:"i",î:"i",ï:"i",Ñ:"N",ñ:"n",Ò:"O",Ó:"O",Ô:"O",Õ:"O",Ö:"O",Ø:"O",ò:"o",ó:"o",ô:"o",õ:"o",ö:"o",ø:"o",Ù:"U",Ú:"U",Û:"U",Ü:"U",ù:"u",ú:"u",û:"u",ü:"u",Ý:"Y",ý:"y",ÿ:"y",Æ:"Ae",æ:"ae",Þ:"Th",þ:"th",ß:"ss",Ā:"A",Ă:"A",Ą:"A",ā:"a",ă:"a",ą:"a",Ć:"C",Ĉ:"C",Ċ:"C",Č:"C",ć:"c",ĉ:"c",ċ:"c",č:"c",Ď:"D",Đ:"D",ď:"d",đ:"d",Ē:"E",Ĕ:"E",Ė:"E",Ę:"E",Ě:"E",ē:"e",ĕ:"e",ė:"e",ę:"e",ě:"e",Ĝ:"G",Ğ:"G",Ġ:"G",Ģ:"G",ĝ:"g",ğ:"g",ġ:"g",ģ:"g",Ĥ:"H",Ħ:"H",ĥ:"h",ħ:"h",Ĩ:"I",Ī:"I",Ĭ:"I",Į:"I",İ:"I",ĩ:"i",ī:"i",ĭ:"i",į:"i",ı:"i",Ĵ:"J",ĵ:"j",Ķ:"K",ķ:"k",ĸ:"k",Ĺ:"L",Ļ:"L",Ľ:"L",Ŀ:"L",Ł:"L",ĺ:"l",ļ:"l",ľ:"l",ŀ:"l",ł:"l",Ń:"N",Ņ:"N",Ň:"N",Ŋ:"N",ń:"n",ņ:"n",ň:"n",ŋ:"n",Ō:"O",Ŏ:"O",Ő:"O",ō:"o",ŏ:"o",ő:"o",Ŕ:"R",Ŗ:"R",Ř:"R",ŕ:"r",ŗ:"r",ř:"r",Ś:"S",Ŝ:"S",Ş:"S",Š:"S",ś:"s",ŝ:"s",ş:"s",š:"s",Ţ:"T",Ť:"T",Ŧ:"T",ţ:"t",ť:"t",ŧ:"t",Ũ:"U",Ū:"U",Ŭ:"U",Ů:"U",Ű:"U",Ų:"U",ũ:"u",ū:"u",ŭ:"u",ů:"u",ű:"u",ų:"u",Ŵ:"W",ŵ:"w",Ŷ:"Y",ŷ:"y",Ÿ:"Y",Ź:"Z",Ż:"Z",Ž:"Z",ź:"z",ż:"z",ž:"z",Ĳ:"IJ",ĳ:"ij",Œ:"Oe",œ:"oe",ŉ:"'n",ſ:"s"});e.exports=r},957:(e,t,n)=>{var r="object"==typeof n.g&&n.g&&n.g.Object===Object&&n.g;e.exports=r},607:(e,t,n)=>{var r=n(705),o=Object.prototype,a=o.hasOwnProperty,i=o.toString,c=r?r.toStringTag:void 0;e.exports=function(e){var t=a.call(e,c),n=e[c];try{e[c]=void 0;var r=!0}catch(e){}var o=i.call(e);return r&&(t?e[c]=n:delete e[c]),o}},689:e=>{var t=RegExp("[\\u200d\\ud800-\\udfff\\u0300-\\u036f\\ufe20-\\ufe2f\\u20d0-\\u20ff\\ufe0e\\ufe0f]");e.exports=function(e){return t.test(e)}},157:e=>{var t=/[a-z][A-Z]|[A-Z]{2}[a-z]|[0-9][a-zA-Z]|[a-zA-Z][0-9]|[^a-zA-Z0-9 ]/;e.exports=function(e){return t.test(e)}},333:e=>{var t=Object.prototype.toString;e.exports=function(e){return t.call(e)}},639:(e,t,n)=>{var r=n(957),o="object"==typeof self&&self&&self.Object===Object&&self,a=r||o||Function("return this")();e.exports=a},140:(e,t,n)=>{var r=n(286),o=n(689),a=n(676);e.exports=function(e){return o(e)?a(e):r(e)}},676:e=>{var t="[\\u0300-\\u036f\\ufe20-\\ufe2f\\u20d0-\\u20ff]",n="\\ud83c[\\udffb-\\udfff]",r="[^\\ud800-\\udfff]",o="(?:\\ud83c[\\udde6-\\uddff]){2}",a="[\\ud800-\\udbff][\\udc00-\\udfff]",i="(?:"+t+"|"+n+")?",c="[\\ufe0e\\ufe0f]?",u=c+i+"(?:\\u200d(?:"+[r,o,a].join("|")+")"+c+i+")*",l="(?:"+[r+t+"?",t,o,a,"[\\ud800-\\udfff]"].join("|")+")",f=RegExp(n+"(?="+n+")|"+l+u,"g");e.exports=function(e){return e.match(f)||[]}},757:e=>{var t="a-z\\xdf-\\xf6\\xf8-\\xff",n="A-Z\\xc0-\\xd6\\xd8-\\xde",r="\\xac\\xb1\\xd7\\xf7\\x00-\\x2f\\x3a-\\x40\\x5b-\\x60\\x7b-\\xbf\\u2000-\\u206f \\t\\x0b\\f\\xa0\\ufeff\\n\\r\\u2028\\u2029\\u1680\\u180e\\u2000\\u2001\\u2002\\u2003\\u2004\\u2005\\u2006\\u2007\\u2008\\u2009\\u200a\\u202f\\u205f\\u3000",o="["+r+"]",a="\\d+",i="["+t+"]",c="[^\\ud800-\\udfff"+r+a+"\\u2700-\\u27bf"+t+n+"]",u="(?:\\ud83c[\\udde6-\\uddff]){2}",l="[\\ud800-\\udbff][\\udc00-\\udfff]",f="["+n+"]",s="(?:"+i+"|"+c+")",d="(?:"+f+"|"+c+")",p="(?:['’](?:d|ll|m|re|s|t|ve))?",m="(?:['’](?:D|LL|M|RE|S|T|VE))?",v="(?:[\\u0300-\\u036f\\ufe20-\\ufe2f\\u20d0-\\u20ff]|\\ud83c[\\udffb-\\udfff])?",b="[\\ufe0e\\ufe0f]?",g=b+v+"(?:\\u200d(?:"+["[^\\ud800-\\udfff]",u,l].join("|")+")"+b+v+")*",y="(?:"+["[\\u2700-\\u27bf]",u,l].join("|")+")"+g,x=RegExp([f+"?"+i+"+"+p+"(?="+[o,f,"$"].join("|")+")",d+"+"+m+"(?="+[o,f+s,"$"].join("|")+")",f+"?"+s+"+"+p,f+"+"+m,"\\d*(?:1ST|2ND|3RD|(?![123])\\dTH)(?=\\b|[a-z_])","\\d*(?:1st|2nd|3rd|(?![123])\\dth)(?=\\b|[A-Z_])",a,y].join("|"),"g");e.exports=function(e){return e.match(x)||[]}},816:(e,t,n)=>{var r=n(389),o=n(833),a=/[\xc0-\xd6\xd8-\xf6\xf8-\xff\u0100-\u017f]/g,i=RegExp("[\\u0300-\\u036f\\ufe20-\\ufe2f\\u20d0-\\u20ff]","g");e.exports=function(e){return(e=o(e))&&e.replace(a,r).replace(i,"")}},469:e=>{var t=Array.isArray;e.exports=t},5:e=>{e.exports=function(e){return null!=e&&"object"==typeof e}},448:(e,t,n)=>{var r=n(239),o=n(5);e.exports=function(e){return"symbol"==typeof e||o(e)&&"[object Symbol]"==r(e)}},29:(e,t,n)=>{var r=n(393),o=n(700),a=r((function(e,t,n){return e+(n?" ":"")+o(t)}));e.exports=a},833:(e,t,n)=>{var r=n(531);e.exports=function(e){return null==e?"":r(e)}},700:(e,t,n)=>{var r=n(805)("toUpperCase");e.exports=r},748:(e,t,n)=>{var r=n(865),o=n(157),a=n(833),i=n(757);e.exports=function(e,t,n){return e=a(e),void 0===(t=n?void 0:t)?o(e)?i(e):r(e):e.match(t)||[]}},76:t=>{"use strict";t.exports=e}},n={};function r(e){var o=n[e];if(void 0!==o)return o.exports;var a=n[e]={exports:{}};return t[e](a,a.exports,r),a.exports}r.n=e=>{var t=e&&e.__esModule?()=>e.default:()=>e;return r.d(t,{a:t}),t},r.d=(e,t)=>{for(var n in t)r.o(t,n)&&!r.o(e,n)&&Object.defineProperty(e,n,{enumerable:!0,get:t[n]})},r.g=function(){if("object"==typeof globalThis)return globalThis;try{return this||new Function("return this")()}catch(e){if("object"==typeof window)return window}}(),r.o=(e,t)=>Object.prototype.hasOwnProperty.call(e,t),r.r=e=>{"undefined"!=typeof Symbol&&Symbol.toStringTag&&Object.defineProperty(e,Symbol.toStringTag,{value:"Module"}),Object.defineProperty(e,"__esModule",{value:!0})};var o={};return(()=>{"use strict";r.r(o),r.d(o,{Connect:()=>O});const e=window.wp.element,t=window.React;var n=r(76);function a(e,t,n){return t in e?Object.defineProperty(e,t,{value:n,enumerable:!0,configurable:!0,writable:!0}):e[t]=n,e}function i(e,t){(null==t||t>e.length)&&(t=e.length);for(var n=0,r=new Array(t);n<t;n++)r[n]=e[n];return r}function c(e,t){if(e){if("string"==typeof e)return i(e,t);var n=Object.prototype.toString.call(e).slice(8,-1);return"Object"===n&&e.constructor&&(n=e.constructor.name),"Map"===n||"Set"===n?Array.from(e):"Arguments"===n||/^(?:Ui|I)nt(?:8|16|32)(?:Clamped)?Array$/.test(n)?i(e,t):void 0}}var u=r(29),l=r.n(u);const f=window.moment;var s=r.n(f),d=r(184),p=r.n(d),m="fdb-page-connect";function v(e,t){var n=Object.keys(e);if(Object.getOwnPropertySymbols){var r=Object.getOwnPropertySymbols(e);t&&(r=r.filter((function(t){return Object.getOwnPropertyDescriptor(e,t).enumerable}))),n.push.apply(n,r)}return n}function b(e){for(var t=1;t<arguments.length;t++){var n=null!=arguments[t]?arguments[t]:{};t%2?v(Object(n),!0).forEach((function(t){a(e,t,n[t])})):Object.getOwnPropertyDescriptors?Object.defineProperties(e,Object.getOwnPropertyDescriptors(n)):v(Object(n)).forEach((function(t){Object.defineProperty(e,t,Object.getOwnPropertyDescriptor(n,t))}))}return e}var g=n.Utils.useDictionary,y=n.Utils.applyDiscount;function x(o){var a,u,f=o.license,d=(0,t.useContext)(n.App.Context).state.billingPortalUrl,v=(a=(0,t.useState)([]),u=2,function(e){if(Array.isArray(e))return e}(a)||function(e,t){var n=null==e?null:"undefined"!=typeof Symbol&&e[Symbol.iterator]||e["@@iterator"];if(null!=n){var r,o,a=[],i=!0,c=!1;try{for(n=n.call(e);!(i=(r=n.next()).done)&&(a.push(r.value),!t||a.length!==t);i=!0);}catch(e){c=!0,o=e}finally{try{i||null==n.return||n.return()}finally{if(c)throw o}}return a}}(a,u)||c(a,u)||function(){throw new TypeError("Invalid attempt to destructure non-iterable instance.\nIn order to be iterable, non-array objects must have a [Symbol.iterator]() method.")}()),x=v[0],_=v[1];(0,t.useEffect)((function(){if(f){var t=f.currentPlanData;if(t){var r="cancelled"!==f.status,o=[{name:"License Type",value:"".concat(l()(t.name))}].concat(function(e){return function(e){if(Array.isArray(e))return i(e)}(e)||function(e){if("undefined"!=typeof Symbol&&null!=e[Symbol.iterator]||null!=e["@@iterator"])return Array.from(e)}(e)||c(e)||function(){throw new TypeError("Invalid attempt to spread non-iterable instance.\nIn order to be iterable, non-array objects must have a [Symbol.iterator]() method.")}()}(f.resources.map((function(t){var n=t.name,r=t.caption,o=void 0===r?"":r,a=t.value,i=void 0===a?"":a,c=t.limit,u=void 0===c?"":c,f=t.units,s=void 0===f?"":f,d=u&&i>u;return{name:l()(n),value:o&&l()(o)||(d?(0,e.createElement)("span",null,(0,e.createElement)("span",{className:"".concat(m,"__resource-limited")},i),u?" / ".concat(u):""):"".concat(i).concat(u?" / ".concat(u):""," ").concat(s)),data:b({},t)}}))),[{name:r?"Next Payment":"Expire",value:f.expired?s()(1e3*f.expired).format("DD MMM YYYY"):"–"},{name:"Price",value:(0,e.createElement)(n.Price,{value:y(f.currentPeriod.price,f.discount),currency:f.currentPeriod.units})}]).map((function(t){return t.data?"space"!==t.data.name?t:b(b({},t),{},{footer:t.data.limit?(0,e.createElement)(n.Progress,{percent:t.data.value/t.data.limit*100}):null}):t}));_([o.slice(0,4),o.slice(4,o.length)])}}}),[f]);var E=g();return!!f&&!!f.currentPlanData&&(0,e.createElement)("div",{className:"".concat(m,"__license-info")},(0,e.createElement)("div",{className:p()("".concat(m,"__license-info-logo"),"".concat(m,"__license-info-logo_").concat(f.currentPlanData.name))}),(0,e.createElement)("div",{className:"".concat(m,"__license-info-body")},(0,e.createElement)("div",{className:p()("".concat(m,"__info-title"),"".concat(m,"__info-title_license"))},E("your-x-account",l()(f.currentPlanData.name))),(0,e.createElement)("div",{className:"".concat(m,"__license-info-restrictions")},x.map((function(t,r){return(0,e.createElement)("div",{key:r},(0,e.createElement)(n.OptionsList,{items:t}))}))),d&&(0,e.createElement)("div",{className:"".concat(m,"__customer_portal_link")},(0,e.createElement)("div",{className:"portal_link_container"},(0,e.createElement)(n.Button,{onClick:function(e){e.preventDefault(),r.g.open(d,"_blank")}},E("stripe-portal-link"))))))}var _=n.Utils.useDictionary;function E(t){var r=t.startLink,o=t.supportLink,a=t.developerLink,i=_();return(0,e.createElement)("div",{className:"".concat(m,"__links")},(0,e.createElement)(n.Link,{className:p()("".concat(m,"__link"),"".concat(m,"__link_start")),href:r,draggable:!1},(0,e.createElement)("div",{className:"".concat(m,"__link-title")},i("getting-started"))),(0,e.createElement)(n.Link,{className:p()("".concat(m,"__link"),"".concat(m,"__link_support")),href:o,draggable:!1},(0,e.createElement)("div",{className:"".concat(m,"__link-title")},i("get-support"))),(0,e.createElement)(n.Link,{className:p()("".concat(m,"__link"),"".concat(m,"__link_developer")),href:a,draggable:!1},(0,e.createElement)("div",{className:"".concat(m,"__link-title")},i("for-developers"))))}E.displayName="Links";var h=n.Utils.useDictionary;function j(){var t=h();return(0,e.createElement)("div",{className:"".concat(m,"__plugin-info")},(0,e.createElement)("div",{className:"".concat(m,"__plugin-info-logo")}),(0,e.createElement)("div",{className:"".concat(m,"__plugin-info-body")},(0,e.createElement)("div",{className:p()("".concat(m,"__info-title"),"".concat(m,"__info-title_plugin"))},t("sign-up-free-account")),(0,e.createElement)("div",{className:"".concat(m,"__plugin-tagline")},t("plugin-tagline")),(0,e.createElement)("ul",{className:"".concat(m,"__plugin-tagline-list")},(0,e.createElement)("li",null,t("tagline-table-chart")),(0,e.createElement)("li",null,t("tagline-integrates")),(0,e.createElement)("li",null,t("tagline-very-fast")),(0,e.createElement)("li",null,t("tagline-securely")))))}function O(r){var o=r.email,a=r.name,i=r.isConnected,c=r.location,u=r.customServer,l=r.onConnect,f=r.onDisconnect,s=r.onResetPassword,d=r.onForgotPassword,p=r.startLink,v=r.supportLink,b=r.developerLink,g=r.license;return(0,e.createElement)("div",{className:m},(0,e.createElement)("div",{className:"".concat(m,"__header")},(0,e.createElement)("div",{className:"".concat(m,"__banner")}),(0,e.createElement)(n.Auth,{email:o,name:a,isConnected:i,location:c,customServer:u,onConnect:l,onDisconnect:f,onResetPassword:s,onForgotPassword:d})),i?(0,e.createElement)(t.Fragment,null,(0,e.createElement)(E,{startLink:p,supportLink:v,developerLink:b}),(0,e.createElement)(x,{license:g})):(0,e.createElement)(j,null))}j.displayName="PluginInfo",O.displayName="Connect"})(),o})()}));