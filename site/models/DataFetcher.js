/* 
 * This file is part of the McThrows package.
 * 
 * (c) 2018 Daniele Ambrosino <mail@danieleambrosino.it>
 * 
 * For the full copyright and license information, please view the LICENSE
 * file distributed with this source code.
 */

var DataFetcher = function () {
  var dataFetcher = this;
//  var cache = false;
//  var lastName = null;
  var listeners = new Array();
  
  this.addListener = function (listener) {
    listeners.push(listener);
  };
  
  this.notifyThrowsLoaded = function (ajaxData) {
    for (var i = 0; i < listeners.length; i++) {
      listeners[i].throwsLoaded(ajaxData);
    }
  };
  
  this.notifyUsersLoaded = function (ajaxData) {
    for (var i = 0; i < listeners.length; i++) {
      listeners[i].usersLoaded(ajaxData);
    }
  };
  
  this.getThrows = function (name) {
//    if (dataFetcher.cache && name === dataFetcher.lastName) {
//      dataFetcher.notifyLoadFinished(dataFetcher.cache);
//      return;
//    }
//    dataFetcher.lastName = name;
    $.ajax({
      url: 'site/ajax.php',
      data: {
        method: 'getThrows',
        parameters: [name]
      },
      dataType: 'json',
      error: function () {
        alert("Qualcosa è andato storto");
      },
      success: function (jsonResponse) {
//        dataFetcher.cache = jsonResponse;
        dataFetcher.notifyThrowsLoaded(jsonResponse);
      }
    });
  };
  
  this.getUsers = function ()
  {
    $.ajax({
      url: 'site/ajax.php',
      data: {
        method: 'getUsers',
        parameters: []
      },
      dataType: 'json',
      error: function () {
        alert("Qualcosa è andato storto");
      },
      success: function (jsonResponse) {
        dataFetcher.notifyUsersLoaded(jsonResponse);
      }
    });
  };
};


var DataFetcherListener = function () {
  this.throwsLoaded = function () {};
  this.usersLoaded = function () {};
};