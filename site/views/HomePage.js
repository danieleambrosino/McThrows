/* 
 * This file is part of the McThrows package.
 * 
 * (c) 2018 Daniele Ambrosino <mail@danieleambrosino.it>
 * 
 * For the full copyright and license information, please view the LICENSE
 * file distributed with this source code.
 */

var HomePage = function () {
  var cp = this;
  var listeners = new Array();

  this.addListener = function (listener) {
    listeners.push(listener);
  };

  this.notifyButtonClicked = function (name) {
    for (var i = 0; i < listeners.length; i++) {
      listeners[i].buttonClicked(name);
    }
  };
  
  this.notifyPageLoaded = function () {
    for (var i = 0; i < listeners.length; i++) {
      listeners[i].pageLoaded();
    }
  };

  this.printCharts = function (serverData) {
    if (serverData.length === 0) {
      alert('Appena inserirai qualche tiro, sarò felice di mostrartelo!');
      return;
    }
    
    // se c'è l'array dei tiri di d20, estrailo perché sarà il primo ad essere stampato (non ha senso ordinarlo)
    var d20Throws = null;
    if (serverData.hasOwnProperty('d20')) {
      d20Throws = serverData.d20;
      delete serverData.d20;
    }
    
    // crea un oggetto costituito da tipoDado: numeroTiri
    var diceCount = {};
    var numberOfThrows = 0;
    for (var dice in serverData) {
      numberOfThrows = 0;
      for (var i = 0; i < serverData[dice].length; i++) {
        numberOfThrows += serverData[dice][i];
      }
      diceCount[dice] = numberOfThrows;
    }
    
    // converti l'oggetto in un array di array
    diceCount = Object.entries(diceCount);
    
    // ridefinisci l'algoritmo di ordinamento in modo tale da 
    // ordinare l'array di array in base ai valori (e non in base alle chiavi)
    diceCount.sort(function(a, b) {
      return b[1] - a[1];
    });
    
    var diceTypes = [];
    if (d20Throws !== null) {
      diceTypes.push('d20');
      // re-inserisci i tiri d20 nei dati
      Object.assign(serverData, {d20: d20Throws});
    }
    // prendi i tipiDado nell'ordine giusto
    for (var i = 0; i < diceCount.length; i++) {
      diceTypes.push(diceCount[i][0]);
    }
    
    // sfuma i vecchi grafici
    $('#charts-area').fadeOut('fast', function()
    {
      // cancella eventuali vecchi grafici
      $('#charts-area').empty();
      // mostra l'area
      $('#charts-area').show();

      // stampa i grafici
      for (var i = 0; i < diceTypes.length; i++) {
        // stabilisci a run-time la posizione più adatta dei grafici
        if (i === 0) {
          var canvasId = 'd20-canvas';
          var node = '<div id="charts-row-1" class="row justify-content-center m-5"><div class="col-6"><canvas id="d20-canvas"></canvas></div></div>';
          $('#charts-area').append(node);
        } else if (i === 1) {
          var canvasId = 'canvas-1';
          var node = '<div id="charts-row-2" class="row justify-content-center m-5"><div class="col-5"><canvas id="canvas-1"></canvas></div></div>';
          $('#charts-row-1').after(node);
        } else if (i === 2) {
          var canvasId = 'canvas-2';
          var node = '<div class="col-5"><canvas id="canvas-2"></canvas></div>';
          $('#charts-row-2').append(node);
        } else if (i === 3) {
          var canvasId = 'canvas-3';
          var node = '<div id="charts-row-3" class="row justify-content-center m-5"><div class="col-4"><canvas id="canvas-3"></canvas></div></div>';
          $('#charts-row-2').after(node);
        } else {
          var canvasId = 'canvas-' + i;
          var node = '<div class="col-4"><canvas id="' + canvasId + '"></canvas></div>';
          $('#charts-row-3').append(node);
        }

        // calcola il tiro medio per dado usando la media pesata
        var average = 0;
        var totalThrows = 0;
        for (var j = 0; j < serverData[diceTypes[i]].length; ++j)
        {
          average += serverData[diceTypes[i]][j] * (j + 1);
          totalThrows += serverData[diceTypes[i]][j];
        }
        average /= totalThrows;
        average = average.toFixed(2);
        
        // stampa il nome del dado sotto il grafico e la media
        $('#' + canvasId).after('<h5 class="text-center">' + diceTypes[i] + ' (media: ' + average + ')' + '</h5>');
        
        // ottieni il canvas-context HTML5 per il rendering del grafico
        var ctx = document.getElementById(canvasId).getContext('2d');

        // genera le etichette per l'asse X
        var labels = [];
        for (var j = 1; j <= serverData[diceTypes[i]].length; j++) {
          labels.push(j);
        }

        var data = serverData[diceTypes[i]];
        var chartData = {
          labels: labels,
          datasets: [
            {
              data: data,
              backgroundColor: 'rgba(0, 51, 153, 0.5)'
            }
          ]
        };
        var options = {
          legend: {
            display: false
          },
          scales: {
            yAxes: [
              {
                ticks: {
                  beginAtZero: true//,
//                  stepSize: 1
                }
              }
            ]
          }
        };

        new Chart(ctx, {
          type: 'bar',
          data: chartData,
          options: options
        });
      }
      
      // allinea pagina ai grafici
      $('html, body').animate({
        scrollTop: $('#charts-area').offset().top
      }, 'slow');
    });
  };

  this.printButtons = function(users)
  {
    if (users.length === 0)
    {
      return;
    }
    var rowNumber = 0;
    for (var i = 0; i < users.length; i++) {
      if ((i % 3) === 0)
      {
        ++rowNumber;
        $('#buttons-area').append(`<div id="buttons-row-${rowNumber}" class="row justify-content-center text-center m-1"></div>`);
      }
      var buttonId = users[i] + '-btn';
      $(`#buttons-row-${rowNumber}`).append(`<div class="col"><button id="${buttonId}" class="btn btn-primary btn-lg">${users[i]}</button></div>`);
      $(`#${buttonId}`).click({
        user: users[i]
      }, function (event) {
        cp.notifyButtonClicked(event.data.user);
      });
    }
    $('#buttons-area').append('<div class="row justify-content-center mt-3"><div class="col-12"><button id="overall-btn" class="btn btn-primary btn-block btn-lg">Overall</button></div></div>')
    $('#overall-btn').click(function() {
      cp.notifyButtonClicked('Overall');
    });
  };
  
};

var HomePageListener = function () {
  this.buttonClicked = function () {};
  this.pageLoaded = function () {};
};
