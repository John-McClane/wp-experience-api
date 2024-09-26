from selenium import webdriver
from selenium.webdriver.chrome.options import Options
from selenium.webdriver.common.by import By
from selenium.webdriver.common.keys import Keys
from selenium.webdriver.support.ui import WebDriverWait
from selenium.webdriver.support import expected_conditions as EC
from selenium.webdriver.common.action_chains import ActionChains
import random
import time
from datetime import datetime
from multiprocessing import Process

verboselogs = 1

def create_browser():
    # Set UserAgent
    # The list of UserAgents to rotate on
    UserAgents = ["Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/130.0.0.0 Safari/537.36" , "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/129.0.0.0 Safari/537.36" , "Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:129.0) Gecko/20100101 Firefox/131.0" , "Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:128.0) Gecko/20100101 Firefox/130.0" , "Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/130.0.0.0 Safari/537.36" , "Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/129.0.0.0 Safari/537.36" , "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/130.0.0.0 Safari/537.36" , "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/129.0.0.0 Safari/537.36"]

    # Set up Chrome options
    chrome_options = Options()
    chrome_options.add_argument("--headless")  # Run in headless mode for faster performance
    chrome_options.add_argument("--window-size=1920,1080")
    chrome_options.add_argument("--no-sandbox")
    chrome_options.add_argument("--disable-dev-shm-usage")
    chrome_options.add_argument("--disable-gpu")

    create_browser.UserAgent = random.choice(UserAgents)
    chrome_options.add_argument(f"--user-agent={create_browser.UserAgent}")

    # Create a new instance of Chrome WebDriver
    browser = webdriver.Chrome(options=chrome_options)
    return browser

def send_keys_to_page(url, key):
    browser = create_browser()
    print(f"Task {key}: ChromeDriver_setup called ")

    # Set up a BaseURL
    base_url = "https://lectures.netmode.ntua.gr"
    links = [["Διαχείριση Δικτύων 2018", "19/11/2018"], ["Διαχείριση Δικτύων 2018", "12/11/2018"], ["Διαχείριση Δικτύων 2018", "05/11/2018"], ["Διαχείριση Δικτύων 2018", "22/10/2018"], ["Διαχείριση Δικτύων 2018", "08/10/2018"], ["Διαχείριση Δικτύων 2017", "17/01/2018"], ["Διαχείριση Δικτύων 2017", "11/12/2017"], ["Διαχείριση Δικτύων 2017", "20/11/2017"], ["Διαχείριση Δικτύων 2017", "30/10/2017"], ["Διαχείριση Δικτύων 2017", "23/10/2017"]]

    # Perform page actions
    browser.get(base_url)
    WebDriverWait(browser, 1).until(EC.presence_of_element_located((By.ID, "site-content")))
    if verboselogs: print(f"Time1: {datetime.today().strftime('%Y/%m/%d %H:%M:%S')}: Task {key}: Title: '{browser.title}' UserAgent: '{create_browser.UserAgent}'")
    rand1 = [random.randint(0,9)]
    for p in rand1:
        actions1 = ActionChains(browser)
        element1 = browser.find_element(By.PARTIAL_LINK_TEXT, links[p][0])
        actions1.move_to_element(element1).perform()
        actions1.click().perform()
        if verboselogs: print(f"Time2: {datetime.today().strftime('%Y/%m/%d %H:%M:%S')}: Task {key}: Title: '{browser.title}' UserAgent: '{create_browser.UserAgent}'")
        actions2 = ActionChains(browser)
        element2 = browser.find_element(By.PARTIAL_LINK_TEXT, links[p][1])
        actions2.move_to_element(element2).perform()
        actions2.click().perform()

    try:
        keys_to_send_ar = [Keys.ARROW_RIGHT, Keys.ARROW_RIGHT, Keys.ARROW_RIGHT, Keys.ARROW_RIGHT, Keys.ARROW_RIGHT, Keys.ARROW_RIGHT]
        keys_to_send_al = [Keys.ARROW_LEFT, Keys.ARROW_LEFT, Keys.ARROW_LEFT, Keys.ARROW_LEFT, Keys.ARROW_LEFT, Keys.ARROW_LEFT]
        keys_to_send = [keys_to_send_ar, keys_to_send_al]
        KeysRandom = random.choice(keys_to_send)

        # Navigate to the page
        browser.get(url)

        # Pause for the page to load
        time.sleep(2)
        if verboselogs: print(f"Time3: {datetime.today().strftime('%Y/%m/%d %H:%M:%S')}: Task {key}: Title: '{browser.title}' UserAgent: '{create_browser.UserAgent}'")
        wait = WebDriverWait(browser, 10).until(EC.element_to_be_clickable((By.ID, "jwp_display_button"))).click()
        time.sleep(random.randint(10,20))

        # Find the relevant field
        input_field = browser.find_element(By.CLASS_NAME, "jwplayer")
        input_field.send_keys(Keys.SPACE)

        for i in range(5):
            time.sleep(5)
            input_field.send_keys(random.randint(0,9))  # Send the specified keys to skip to a random time in video
            time.sleep(random.randint(10,20))
            input_field.send_keys(Keys.SPACE)
            for k in keys_to_send_ar:                   # Loop through the keys and send them with a pause
                input_field.send_keys(KeysRandom)
                time.sleep(1)                           # Pause for 1 second
            input_field.send_keys(Keys.SPACE)
            time.sleep(random.randint(10,20))
            input_field.send_keys(Keys.SPACE)

        randbool = bool(random.randint(0,1))
        if randbool:
            time.sleep(5)
            input_field.send_keys(9)
            time.sleep(random.randint(10,20))
            input_field.send_keys(Keys.SPACE)
            for k in keys_to_send_ar:
                input_field.send_keys(KeysRandom)
                time.sleep(1)                           # Pause for 1 second
            input_field.send_keys(Keys.SPACE)

        time.sleep(5)                                   # Pause to see the result before closing

    finally:
        browser.quit()                                  # Close the browser
        if verboselogs: print(f"Time4: {datetime.today().strftime('%Y/%m/%d %H:%M:%S')}: Task {key}: Title: '{browser.title}' UserAgent: '{create_browser.UserAgent}'")

if __name__ == "__main__":
    # Define the URLs and keys to send
    # ints = [random.randint(0,9)]
    pages = [
        {"url": "https://lectures.netmode.ntua.gr/?lectures=%CE%B4%CE%B9%CE%B1%CF%87%CE%B5%CE%AF%CF%81%CE%B9%CF%83%CE%B7-%CE%B4%CE%B9%CE%BA%CF%84%CF%8D%CF%89%CE%BD-%CE%B5%CF%85%CF%86%CF%85%CE%AE-%CE%B4%CE%AF%CE%BA%CF%84%CF%85%CE%B1-9%CE%BF-%CE%B5-8-19-11-2018", "key": "1"},
        {"url": "https://lectures.netmode.ntua.gr/?lectures=%CE%B4%CE%B9%CE%B1%CF%87%CE%B5%CE%AF%CF%81%CE%B9%CF%83%CE%B7-%CE%B4%CE%B9%CE%BA%CF%84%CF%8D%CF%89%CE%BD-%CE%B5%CF%85%CF%86%CF%85%CE%AE-%CE%B4%CE%AF%CE%BA%CF%84%CF%85%CE%B1-9%CE%BF-%CE%B5-7-12-11-2018", "key": "2"},
        {"url": "https://lectures.netmode.ntua.gr/?lectures=%CE%B4%CE%B9%CE%B1%CF%87%CE%B5%CE%AF%CF%81%CE%B9%CF%83%CE%B7-%CE%B4%CE%B9%CE%BA%CF%84%CF%8D%CF%89%CE%BD-%CE%B5%CF%85%CF%86%CF%85%CE%AE-%CE%B4%CE%AF%CE%BA%CF%84%CF%85%CE%B1-9%CE%BF-%CE%B5-6-5-11-2018", "key": "3"},
        {"url": "https://lectures.netmode.ntua.gr/?lectures=%CE%B4%CE%B9%CE%B1%CF%87%CE%B5%CE%AF%CF%81%CE%B9%CF%83%CE%B7-%CE%B4%CE%B9%CE%BA%CF%84%CF%8D%CF%89%CE%BD-%CE%B5%CF%85%CF%86%CF%85%CE%AE-%CE%B4%CE%AF%CE%BA%CF%84%CF%85%CE%B1-9%CE%BF-%CE%B5-5-22-10-2018", "key": "4"},
        {"url": "https://lectures.netmode.ntua.gr/?lectures=%CE%B4%CE%B9%CE%B1%CF%87%CE%B5%CE%AF%CF%81%CE%B9%CF%83%CE%B7-%CE%B4%CE%B9%CE%BA%CF%84%CF%8D%CF%89%CE%BD-%CE%B5%CF%85%CF%86%CF%85%CE%AE-%CE%B4%CE%AF%CE%BA%CF%84%CF%85%CE%B1-9%CE%BF-%CE%B5-2-8-10-2018", "key": "5"},
        {"url": "https://lectures.netmode.ntua.gr/?lectures=%CE%B4%CE%B9%CE%B1%CF%87%CE%B5%CE%AF%CF%81%CE%B9%CF%83%CE%B7-%CE%B4%CE%B9%CE%BA%CF%84%CF%8D%CF%89%CE%BD-%CE%B5%CF%85%CF%86%CF%85%CE%AE-%CE%B4%CE%AF%CE%BA%CF%84%CF%85%CE%B1-9%CE%BF-%CE%B5-63", "key": "6"},
        {"url": "https://lectures.netmode.ntua.gr/?lectures=%CE%B4%CE%B9%CE%B1%CF%87%CE%B5%CE%AF%CF%81%CE%B9%CF%83%CE%B7-%CE%B4%CE%B9%CE%BA%CF%84%CF%8D%CF%89%CE%BD-%CE%B5%CF%85%CF%86%CF%85%CE%AE-%CE%B4%CE%AF%CE%BA%CF%84%CF%85%CE%B1-9%CE%BF-%CE%B5-58", "key": "7"},
        {"url": "https://lectures.netmode.ntua.gr/?lectures=%CE%B4%CE%B9%CE%B1%CF%87%CE%B5%CE%AF%CF%81%CE%B9%CF%83%CE%B7-%CE%B4%CE%B9%CE%BA%CF%84%CF%8D%CF%89%CE%BD-%CE%B5%CF%85%CF%86%CF%85%CE%AE-%CE%B4%CE%AF%CE%BA%CF%84%CF%85%CE%B1-9%CE%BF-%CE%B5-55", "key": "8"},
        {"url": "https://lectures.netmode.ntua.gr/?lectures=%CE%B4%CE%B9%CE%B1%CF%87%CE%B5%CE%AF%CF%81%CE%B9%CF%83%CE%B7-%CE%B4%CE%B9%CE%BA%CF%84%CF%8D%CF%89%CE%BD-%CE%B5%CF%85%CF%86%CF%85%CE%AE-%CE%B4%CE%AF%CE%BA%CF%84%CF%85%CE%B1-9%CE%BF-%CE%B5-50", "key": "9"},
        {"url": "https://lectures.netmode.ntua.gr/?lectures=%CE%B4%CE%B9%CE%B1%CF%87%CE%B5%CE%AF%CF%81%CE%B9%CF%83%CE%B7-%CE%B4%CE%B9%CE%BA%CF%84%CF%8D%CF%89%CE%BD-%CE%B5%CF%85%CF%86%CF%85%CE%AE-%CE%B4%CE%AF%CE%BA%CF%84%CF%85%CE%B1-9%CE%BF-%CE%B5-49", "key": "10"}
    ]

    processes = []
    print(f"Time0: {datetime.today().strftime('%Y/%m/%d %H:%M:%S')}: Starting scraping with multiprocessing ...")

    start = time.time()
    # Select N random pages
    random_pages = random.sample(pages, 4)

    # Create a process for each page
    for page in random_pages:
        process = Process(target=send_keys_to_page, args=(page["url"], page["key"]))
        processes.append(process)
        process.start()

    # Wait for all processes to finish
    for process in processes:
        process.join()

    end = time.time()
    print(f"Time5: {datetime.today().strftime('%Y/%m/%d %H:%M:%S')}: Ending scraping with multiprocessing ...")
    print(f"Time for scraping with multiprocessing: {round(end-start,2)} seconds")
    print(f"All tasks completed.")