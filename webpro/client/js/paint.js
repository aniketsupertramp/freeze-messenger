$.when(
  $.ajax({
      url: 'client/data/config.json',
      dataType: 'json',
      success: function(data) {
        window.fim_config = data;
      }
  }),
  $.ajax({
      url: 'client/data/language_enGB.json',
      dataType: 'json',
      success: function(data) {
        window.phrases = data;
      },
      async: false,
      cache: true,
    })
).then(function() {

  $.ajax({
    url: 'client/data/templates.json',
    dataType: 'json',
    success: function(data) {
      for (i in data) {
        data[i] = data[i].replace(/\{\{\{\{([a-zA-Z0-9]+)\}\}\}\}/g, function($1, $2) {
            return window.phrases[$2];
          }
        );
      }

      window.templates = data;

      $(document).ready(function() {
        $('body').append(window.templates.main);
        $('body').append(window.templates.chatTemplate);
        $('body').append(window.templates.contextMenu);

        $.getScript('client/js/fim-all.js');
      });
    },
    async: false,
    cache: true
  });
});