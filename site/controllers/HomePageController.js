/* 
 * This file is part of the McThrows package.
 * 
 * (c) 2018 Daniele Ambrosino <mail@danieleambrosino.it>
 * 
 * For the full copyright and license information, please view the LICENSE
 * file distributed with this source code.
 */

var HomePageController = function (dataFetcher, homePage) {
  var hpListener = {
    buttonClicked: function (name) {
      dataFetcher.getThrows(name);
    },
    pageLoaded: function () {
      dataFetcher.getUsers();
    }
  };
  
  var dataFetcherListener = {
    throwsLoaded: function (response) {
      homePage.printCharts(response);
    },
    usersLoaded: function (users) {
      homePage.printButtons(users);
      $(".collapse").fadeIn('slow');
    }
  };
  
  homePage.addListener(hpListener);
  dataFetcher.addListener(dataFetcherListener);
};