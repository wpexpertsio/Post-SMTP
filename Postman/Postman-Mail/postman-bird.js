jQuery(function ($) {
  if (typeof transports === "undefined") return;
  const bird = {
    handleTransportChange: function (name) {
      $(".bird_advanced_setting").toggle(name === "bird_api");
      if (name === "bird_api") {
        $("div.transport_setting, div.authentication_setting").hide();
        $("#bird_settings").prop("hidden", false).show();
      } else {
        $("#bird_settings").hide();
      }
    },
    handleConfigurationResponse: function (response) {
      const selected = response.configuration.transport_type === "bird_api";
      $("section.wizard_bird").toggle(selected);
      if (selected) {
        $("#input_auth_type").val(response.configuration.auth_type);
        $("#input_port").val(response.configuration.port);
        redirectUrlWarning = false;
      }
    },
  };
  transports.push(bird);
  bird.handleTransportChange($("select#input_transport_type").val());
});
