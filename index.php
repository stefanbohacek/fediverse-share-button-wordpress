<?php
/*
    Plugin Name: Fediverse sharing button
    Description: Let your website's visitors share your site with the fediverse.
    Version:     1.0.0
    Author:      Stefan Bohacek
*/

class FTF_Fediverse_Sharing_Button
{

  function __construct()
  {
    add_action("init", array($this, "enqueue_scripts_and_styles"));
    add_action("admin_init", array($this, "settings_init"));
    add_action("admin_menu", array($this, "add_settings_page"));
    add_filter("plugin_action_links_ftf-fediverse-sharing-button.php", array($this, "settings_page_link"));
    add_filter("plugin_action_links_" . plugin_basename(__FILE__), array($this, "settings_page_link"));
    add_filter("plugin_action_links_ftf-fediverse-sharing-button/index.php", array($this, "settings_page_link"));
    add_filter("the_content", array($this, "insert_sharing_button_content"), 999999);
    add_filter("wp_footer", array($this, "insert_sharing_button_home"), 999999);
    add_filter("term_description", array($this, "insert_sharing_button_archive"), 999999);
    add_filter("rest_api_init", array($this, "add_fediverse_server_info_endpoint"), 999999);
  }

  function get_default_sharing_prompt()
  {
    return "Share this page from your <a target='_blank' href='https://jointhefediverse.net/'>fediverse</a> server";
  }

  function get_sharing_button_html()
  {
    $sharing_prompt = html_entity_decode(get_option("ftf_fsb_sharing_prompt", self::get_default_sharing_prompt()));

    if (empty($sharing_prompt)) {
      $sharing_prompt = self::get_default_sharing_prompt();
    }

    $icons_url = plugin_dir_url(__FILE__) . "assets/icons";

    return <<<HTML
    <form class="fsb-prompt">
      <label>{$sharing_prompt}</label>
      <div class="fsb-input-group mb-3">
        <span class="fsb-input-group-text">https://</span>
        <input required
          type="text"
          name="fediverse-domain"
          placeholder="server.social"
          class="fsb-input fsb-domain"
          aria-label="Server domain">
        <button class="fsb-button"
          type="submit"><img src="{$icons_url}/fediverse.svg"
            class="fsb-icon"></span>Share</button>
      </div>
      <p class="fsb-support-note fsb-d-none">This server does not support sharing. Please visit <a
          class="fsb-support-note-link"
          target="_blank"
          href=""></a>.</p>
    </form>
    HTML;
  }

  function insert_sharing_button_archive($description)
  {
    $show_sharing_button = false;

    if (is_category()) {
      if (get_option("ftf_fsb_location_category_pages", "on") === "on") {
        $show_sharing_button = true;
      }
    } elseif (is_tag()) {
      if (get_option("ftf_fsb_location_tag_pages", "on") === "on") {
        $show_sharing_button = true;
      }
    }

    if ($show_sharing_button) {
      $sharing_button_html = self::get_sharing_button_html();
      $description = $description . $sharing_button_html;
    }

    return $description;
  }

  function insert_sharing_button_home()
  {
    $show_sharing_button = false;

    if (is_home() && get_option("ftf_fsb_location_front_page", "on") === "on") {

      $sharing_button_html = self::get_sharing_button_html();
      echo "<div style='max-width: 500px; margin: 0 auto 5rem;'>$sharing_button_html</div>";
    }
  }

  function insert_sharing_button_content($content)
  {
    global $post;
    $show_sharing_button = false;

    if (is_front_page($post)) {
      if (!is_home($post)) {
        if (get_option("ftf_fsb_location_front_page", "on") === "on") {
          $show_sharing_button = true;
        }
      }
    } elseif (is_page($post)) {
      if (get_option("ftf_fsb_location_pages", "on") === "on") {
        $show_sharing_button = true;
      }
    } elseif (is_single($post)) {
      if ($post->post_type === "post" && get_option("ftf_fsb_location_articles", "on") === "on") {
        $show_sharing_button = true;
      } elseif (get_option("ftf_fsb_location_cpt_" . $post->post_type, "on") === "on") {
        $show_sharing_button = true;
      }
    }

    if ($show_sharing_button) {
      $sharing_button_html = self::get_sharing_button_html();
      $content = $content . $sharing_button_html;
    }

    return  $content;
  }

  function enqueue_scripts_and_styles()
  {
    $js_file_path = plugin_dir_path(__FILE__) . "dist/js/main.js";
    wp_register_script("ftf-fsb-main-script", plugin_dir_url(__FILE__) . "dist/js/main.js", array(), filemtime($js_file_path), array(
      "in_footer" => true,
      "strategy"  => "defer",
    ));

    $plugin_data = get_file_data(__DIR__ . '/index.php', array('Version' => 'Version'), false);
    $plugin_version = $plugin_data['Version'];

    $use_external_fediverse_info_server =  get_option("ftf_fsb_location_external_fediverse_info_server", "") === "on";

    wp_localize_script("ftf-fsb-main-script", "ftf_fediverse_sharing_button", array(
      "ajax_url" => admin_url("admin-ajax.php"),
      "blog_url" => get_site_url(),
      "plugin_url" => plugin_dir_url(__FILE__),
      "nonce" => wp_create_nonce("ftf-fediverse-sharing-button"),
      "config" => array(
        // "theme" => $theme,
        "use_external_fediverse_info_server" => $use_external_fediverse_info_server,
      ),
      "version" => $plugin_version
    ));

    wp_enqueue_script("ftf-fsb-main-script");

    $style = get_option("ftf_fsb_style", "default");

    if (empty($style)) {
      $style = "default";
    }

    if ($style === "default") {
      $css_file_path = plugin_dir_path(__FILE__) . "dist/css/main.min.css";
      wp_register_style("ftf-fsb-styles", plugin_dir_url(__FILE__) . "dist/css/main.min.css", array(), filemtime($css_file_path), "all");
      wp_enqueue_style("ftf-fsb-styles");
    }
  }

  function add_settings_page()
  {
    add_options_page(
      "Fediverse Sharing Button",
      "Fediverse Sharing Button",
      "manage_options",
      "ftf-fediverse-sharing-button",
      array($this, "render_settings_page")
    );
  }

  function render_settings_page()
  {
?>
    <div class="wrap">
      <h1>Fediverse sharing button</h1>
      <form action="options.php" method="post">
        <?php
        settings_fields("FTF_Fediverse_Sharing_Button");
        do_settings_sections("FTF_Fediverse_Sharing_Button");
        submit_button();
        ?>
      </form>
    </div>
  <?php
  }

  function settings_init()
  {
    register_setting("FTF_Fediverse_Sharing_Button", "ftf_fsb_style", "esc_attr");
    register_setting("FTF_Fediverse_Sharing_Button", "ftf_fsb_location_on_page", "esc_attr");
    register_setting("FTF_Fediverse_Sharing_Button", "ftf_fsb_sharing_prompt", "esc_attr");
    register_setting("FTF_Fediverse_Sharing_Button", "ftf_fsb_location_external_fediverse_info_server", "esc_attr");

    $button_locations = self::get_button_locations();

    foreach ($button_locations as $location) {
      register_setting("FTF_Fediverse_Sharing_Button", "ftf_fsb_location_" . $location["name"], "esc_attr");
    }

    $custom_post_types = self::get_custom_post_types();

    foreach ($custom_post_types as $cpt) {
      register_setting("FTF_Fediverse_Sharing_Button", "ftf_fsb_location_cpt_" . $cpt->name, "esc_attr");
    }

    add_settings_section(
      "ftf_fediverse_sharing_button_settings",
      __("", "wordpress"),
      array($this, "render_settings_form"),
      "FTF_Fediverse_Sharing_Button"
    );
  }

  function get_button_locations()
  {
    $locations = array(
      array(
        "name" => "front_page",
        "label" => "Home page",
      ),
      array(
        "name" => "articles",
        "label" => "Articles",
      ),
      array(
        "name" => "pages",
        "label" => "Pages",
      ),
      array(
        "name" => "category_pages",
        "label" => "Category pages",
      ),
      // array(
      //   "name" => "author_pages",
      //   "label" => "Author pages",
      // ),
      array(
        "name" => "tag_pages",
        "label" => "Tag pages",
      ),
    );

    return $locations;
  }

  function get_custom_post_types()
  {
    $custom_post_types = get_post_types(array(
      "public"   => true,
      "_builtin" => false,
    ), "objects", "and");

    return $custom_post_types;
  }

  function render_settings_form()
  {
    $style = get_option("ftf_fsb_style", "default");

    if (empty($style)) {
      $style = "default";
    }

    $button_location_on_page = get_option("ftf_fsb_location_on_page", "after_content");
    $sharing_prompt = html_entity_decode(get_option("ftf_fsb_sharing_prompt"));

    if (empty($sharing_prompt)) {
      $sharing_prompt = self::get_default_sharing_prompt();
    }
  ?>
    <h3>Sharing prompt</h3>
    <p>Customize the note shown to your website's visitors.</p>
    <?php
    wp_editor($sharing_prompt, "ftf_fsb_sharing_prompt", array(
      "wpautop"       => true,
      "media_buttons" => false,
      "textarea_name" => "ftf_fsb_sharing_prompt",
      "textarea_rows" => 10,
      "teeny"         => true
    ));
    ?>

    <p>
      <strong>Show sharing button on the following pages</strong>
    </p>
    <ul>
      <?php
      $button_locations = self::get_button_locations();

      foreach ($button_locations as $location) {
        $is_checked = get_option("ftf_fsb_location_" . $location["name"], "on") === "on";
      ?>
        <li>
          <label>
            <input type="checkbox" name="ftf_fsb_location_<?php echo $location["name"]; ?>" value="on" <?php checked($is_checked, true) ?>>
            <?php echo $location["label"]; ?>
          </label>
        </li>
      <?php } ?>
    </ul>
    <?php
    $custom_post_types = self::get_custom_post_types();
    if (!empty($custom_post_types)) { ?>

      <p>
        <strong>Show sharing button on the following custom post types</strong>
      </p>

      <ul>
        <?php
        $custom_post_types = self::get_custom_post_types();

        foreach ($custom_post_types  as $cpt) {
          $is_checked = get_option("ftf_fsb_location_cpt_" . $cpt->name, "on") === "on";
        ?>
          <li>
            <label>
              <input type="checkbox" name="ftf_fsb_location_cpt_<?php echo $cpt->name; ?>" value="on" <?php checked($is_checked, true) ?>>
              <?php echo $cpt->label; ?>
            </label>
          </li>
        <?php } ?>
      </ul>
    <?php } ?>
    <p class="description">Note that some themes may interfere with the display of the sharing button on specific types of pages. Please <a href="https://stefanbohacek.com/contact/">reach out</a> if you encounter such issues.</p>

    <?php
    $use_external_fediverse_info_server =  get_option("ftf_fsb_location_external_fediverse_info_server", "") === "on";
    ?>

    <label>
      <input type="checkbox" name="ftf_fsb_location_external_fediverse_info_server" value="on" <?php checked($use_external_fediverse_info_server, true) ?>>
      <strong>Use external service for fetching fediverse server information</strong>
    </label>

    <p class="description">If you experience any slowness while using this plugin, enabling this option will switch to an external server for fetching fediverse server information.</p>


    <h3>About</h3>
    <ul class="ul-disc">
      <li>
        <a href="https://stefanbohacek.com/project/fediverse-sharing-button/">
          About the plugin
        </a>
      </li>
      <li>
        <a href="https://github.com/stefanbohacek/fediverse-share-button-wordpress">
          View source
        </a>
      </li>
      <li>
        <a href="https://stefanbohacek.com/contact/">
          Contact author
        </a>
      </li>
    </ul>
<?php }

  function settings_page_link($links)
  {
    $url = esc_url(add_query_arg(
      "page",
      "ftf-fediverse-sharing-button",
      get_admin_url() . "admin.php"
    ));
    $settings_link = "<a href='$url'>" . __("Settings") . "</a>";
    array_push(
      $links,
      $settings_link
    );
    return $links;
  }

  function add_fediverse_server_info_endpoint()
  {
    register_rest_route("ftf_fsb/v1", "fediverse-server-info", array(
      "methods" => "GET",
      "callback" => array($this, "get_fediverse_server_info"),
    ));
  }

  function get_fediverse_server_info(\WP_REST_Request $request)
  {
    $domain = $request["domain"];

    $response = array(
      "domain" => $domain,
      "software" => false
    );

    if (!empty($domain)) {
      $fediverse_server_data = self::load_fediverse_server_info();

      if (array_key_exists($domain, $fediverse_server_data)) {
        $response["software"]["name"] = $fediverse_server_data[$domain];
      } else {
        $response["software"] = self::get_node_info($domain);
      }
    }

    return $response;
  }

  function load_fediverse_server_info()
  {
    $server_data_file_path = WP_PLUGIN_DIR . "/fediverse-share-button/data/server-info.csv";
    $lines = explode("\n", file_get_contents($server_data_file_path));
    $headers = str_getcsv(array_shift($lines));
    $data = array();
    foreach ($lines as $line) {
      $data[str_getcsv($line)[0]] = str_getcsv($line)[1];
    }

    return $data;
  }

  function get_node_info($domain)
  {
    $software = array();
    $response = wp_remote_get("https://$domain/.well-known/nodeinfo");

    if (is_array($response) && ! is_wp_error($response)) {
      $body = json_decode($response["body"]);
      if (!empty($body->links)) {
        $link = $body->links[0]->href;
        $response = wp_remote_get($link);

        if (is_array($response) && ! is_wp_error($response)) {
          $body = json_decode($response["body"]);
          if (!empty($body->software)) {
            $software = $body->software;
          }
        }
      }
    }

    return $software;
  }
}

$FTF_Fediverse_Sharing_Button_init = new FTF_Fediverse_Sharing_Button();
