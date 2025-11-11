const config = require('flarum-webpack-config');
const path = require('path');

module.exports = () => {
  const baseConfig = config();

  return {
    ...baseConfig,
    output: {
      ...baseConfig.output,
      path: path.resolve(__dirname, 'js/dist'),
    },
  };
};
