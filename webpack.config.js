const path = require("path");

module.exports = {
  mode: "production",
  entry: {
    main: "./src/scripts/main.js",
    onReady: "./src/scripts/onReady.js",
    getSelectedText: "./src/scripts/getSelectedText.js",
    hidePlatformSupportVisibilityNote:
      "./src/scripts/hidePlatformSupportVisibilityNote.js",
    knownSoftware: "./src/scripts/knownSoftware.js",
    notes: "./src/scripts/notes.js",
    supportedSoftware: "./src/scripts/supportedSoftware.js",
    truncate: "./src/scripts/truncate.js",
    updateIcon: "./src/scripts/updateIcon.js",
    updateTheIcon: "./src/scripts/updateTheIcon.js",
    canUseLocalStorage: "./src/scripts/canUseLocalStorage.js",
    checkPlatformSupport: "./src/scripts/checkPlatformSupport.js",
    doneTyping: "./src/scripts/doneTyping.js",
    getDomain: "./src/scripts/getDomain.js",
    getFSBPath: "./src/scripts/getFSBPath.js",
    getPageDescription: "./src/scripts/getPageDescription.js",
    getPageTitle: "./src/scripts/getPageTitle.js",
    getPageURL: "./src/scripts/getPageURL.js",
    styles: "./src/styles/main.scss",
  },
  output: {
    path: path.resolve(__dirname, "dist/js"),
    filename: "[name].js",
  },
  module: {
    rules: [
      {
        test: /\.js$/,
        use: [
          {
            loader: "babel-loader",
            options: {
              presets: ["@babel/preset-env"],
            },
          },
        ],
      },
      {
        test: /\.scss$/,
        use: [
          {
            loader: "file-loader",
            options: {
              name: "../css/[name].min.css",
              sassOptions: {
                style: "compressed",
                // indentWidth: 4,
                includePaths: ["src/styles"],
              },
            },
          },
          {
            loader: "extract-loader",
          },
          {
            loader: "css-loader?-url",
          },
          {
            loader: "sass-loader",
          },
        ],
      },
    ],
  },
  watchOptions: {
    poll: 1000,
  },
};
