import checkPlatformSupport from "./checkPlatformSupport.js";
import getDomain from "./getDomain.js";
import knownSoftware from "./knownSoftware.js";
import supportedSoftware from "./supportedSoftware.js";
import updateTheIcon from "./updateTheIcon.js";

export default async (el) => {
  const shareBtn = el.parentElement.getElementsByClassName("fsb-button")[0];
  const domain = getDomain(el.value);

  const fediverseInfoServer = ftf_fediverse_sharing_button.config
    .use_external_fediverse_info_server
    ? `https://fediverse-info.stefanbohacek.dev/node-info?domain=${domain}&onlysoftware=true`
    : `${ftf_fediverse_sharing_button.blog_url}/wp-json/ftf_fsb/v1/fediverse-server-info?domain=${domain}`;

  const resp = await fetch(fediverseInfoServer);

  shareBtn.innerHTML = shareBtn.innerHTML.replace("Loading", "Share");

  const respJSON = await resp.json();
  const software = respJSON?.software?.name;
  const iconEl = el.parentElement.getElementsByClassName("fsb-icon")[0];

  el.dataset.software = software;
  window.fsbGlobalSoftware = software;

  checkPlatformSupport(shareBtn);

  if (software && knownSoftware.includes(software)) {
    updateTheIcon(iconEl, software);

    if (supportedSoftware.includes(software)) {
      shareBtn.disabled = false;
    }
  } else {
    updateTheIcon(iconEl, "question");
  }
};
