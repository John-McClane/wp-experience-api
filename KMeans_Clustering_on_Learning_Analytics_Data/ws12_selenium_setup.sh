#!/usr/bin/bash

# Define variables
HOME_DIR="$HOME"
CHROME_URL='https://googlechromelabs.github.io/chrome-for-testing/last-known-good-versions-with-downloads.json'

echo "Changing to home directory..."
cd "$HOME_DIR" || { echo "Error: Failed to change directory to $HOME_DIR"; exit 1; }

echo "Updating the repository and any packages..."
sudo apt update && sudo apt upgrade -y

echo "Installing prerequisite system packages..."
sudo apt install wget curl unzip jq -y

echo "Setting metadata for Google Chrome repository..."
meta_data=$(curl "$CHROME_URL")

echo "Downloading the latest Chrome binary..."
chrome_url=$(echo "$meta_data" | jq -r '.channels.Stable.downloads.chrome[0].url')
wget "$chrome_url" || { echo "Error: Failed to download Chrome binary"; exit 1; }

echo "Installing Chrome dependencies..."
sudo apt install -y ca-certificates fonts-liberation libappindicator3-1 libasound2 \
                    libatk-bridge2.0-0 libatk1.0-0 libc6 libcairo2 libcups2 libdbus-1-3 \
                    libexpat1 libfontconfig1 libgbm1 libgcc1 libglib2.0-0 libgtk-3-0 \
                    libnspr4 libnss3 libpango-1.0-0 libpangocairo-1.0-0 libstdc++6 \
                    libx11-6 libx11-xcb1 libxcb1 libxcomposite1 libxcursor1 libxdamage1 \
                    libxext6 libxfixes3 libxi6 libxrandr2 libxrender1 libxss1 libxtst6 \
                    lsb-release wget xdg-utils || { echo "Error: Failed to install Chrome dependencies"; exit 1; }

echo "Unzipping the Chrome binary file..."
unzip chrome-linux64.zip || { echo "Error: Failed to unzip Chrome binary"; exit 1; }

echo "Downloading the latest Chromedriver..."
chromedriver_url=$(echo "$meta_data" | jq -r '.channels.Stable.downloads.chromedriver[0].url')
wget "$chromedriver_url" || { echo "Error: Failed to download Chromedriver"; exit 1; }

echo "Unzipping the Chromedriver binary file..."
unzip chromedriver-linux64.zip || { echo "Error: Failed to unzip Chromedriver binary"; exit 1; }

echo "Installing Selenium..."
python3 -m pip install selenium --break-system-packages || { echo "Error: Failed to install Selenium"; exit 1; }

echo "Removing archive files..."
rm chrome-linux64.zip chromedriver-linux64.zip

echo "Setup completed successfully."
