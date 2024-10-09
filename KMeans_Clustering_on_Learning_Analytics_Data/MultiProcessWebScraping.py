from selenium import webdriver
from selenium.webdriver.chrome.options import Options
from selenium.webdriver.chrome.service import Service
from selenium.webdriver.common.by import By
from selenium.webdriver.common.keys import Keys
from selenium.webdriver.support.ui import WebDriverWait
from selenium.webdriver.support import expected_conditions as EC
from selenium.webdriver.common.action_chains import ActionChains
import os.path
import random
import time
from datetime import datetime
from multiprocessing import Process
from fake_useragent import UserAgent

verboselogs = 1
headless = 1

def create_browser(agent):
    # Set up Chrome options
    chrome_options = Options()
    homedir = os.path.expanduser("~")
    chrome_options.binary_location = f"{homedir}/chrome-linux64/chrome"
    webdriver_service = Service(f"{homedir}/chromedriver-linux64/chromedriver")

    if headless:
        chrome_options.add_argument("--headless")  # Run in headless mode for faster performance
        # chrome_options.add_argument("--window-size=1920,1080")
        chrome_options.add_argument("--window-size=1024,768")
    chrome_options.add_argument("--no-sandbox")
    chrome_options.add_argument("--disable-dev-shm-usage")
    chrome_options.add_argument("--disable-gpu")

    # ua = UserAgent()
    # create_browser.UserAgent = ua.random
    # create_browser.UserAgent = random.choices(UserAgents, weights=(10, 10, 7, 7, 5, 5, 3, 3, 5, 5, 3, 3), k=1)
    # create_browser.UserAgent = random.choices(UserAgents, weights=(1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1), k=1)
    # create_browser.UserAgent = random.choices(UserAgents, k=1)
    # create_browser.UserAgent = random.sample(UserAgents, 1)

    chrome_options.add_argument(f"--user-agent={agent}")
    print(f"Time1: {datetime.today().strftime('%Y/%m/%d %H:%M:%S')}:  agent: '{agent}'")

    # Create a new instance of Chrome WebDriver
    # browser = webdriver.Chrome(options=chrome_options)
    browser = webdriver.Chrome(service=webdriver_service, options=chrome_options)
    return browser

def send_keys_to_page(url, key):
    # Set UserAgent
    # The list of UserAgents to rotate on
    UserAgents = [
        # Windows Chrome
        "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/130.0.0.0 Safari/537.36",
        "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/129.0.0.0 Safari/537.36",
        "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/128.0.0.0 Safari/537.36",
        "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/127.0.0.0 Safari/537.36",
        "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/126.0.0.0 Safari/537.36",
        "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/125.0.0.0 Safari/537.36",
        # Windows Edge
        "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/130.0.0.0 Safari/537.36 Edg/130.0.0.0",
        "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/129.0.0.0 Safari/537.36 Edg/129.0.0.0",
        "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/128.0.0.0 Safari/537.36 Edg/128.0.0.0",
        "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/127.0.0.0 Safari/537.36 Edg/127.0.0.0",
        "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/126.0.0.0 Safari/537.36 Edg/126.0.0.0",
        "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/125.0.0.0 Safari/537.36 Edg/125.0.0.0",
        # Windows Firefox
        "Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:130.0) Gecko/20100101 Firefox/130.0",
        "Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:129.0) Gecko/20100101 Firefox/129.0",
        "Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:128.0) Gecko/20100101 Firefox/128.0",
        "Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:127.0) Gecko/20100101 Firefox/127.0",
        "Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:126.0) Gecko/20100101 Firefox/126.0",
        "Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:125.0) Gecko/20100101 Firefox/125.0",
        # Linux Chrome
        "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/130.0.0.0 Safari/537.36",
        "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/129.0.0.0 Safari/537.36",
        "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/128.0.0.0 Safari/537.36",
        "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/127.0.0.0 Safari/537.36",
        "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/126.0.0.0 Safari/537.36",
        "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/125.0.0.0 Safari/537.36",
        # Linux Edge
        "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/130.0.0.0 Safari/537.36 Edg/130.0.0.0",
        "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/129.0.0.0 Safari/537.36 Edg/129.0.0.0",
        "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/128.0.0.0 Safari/537.36 Edg/128.0.0.0",
        "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/127.0.0.0 Safari/537.36 Edg/127.0.0.0",
        "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/126.0.0.0 Safari/537.36 Edg/126.0.0.0",
        "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/125.0.0.0 Safari/537.36 Edg/125.0.0.0",
        # Linux Firefox
        "Mozilla/5.0 (X11; Linux x86_64; rv:130.0) Gecko/20100101 Firefox/130.0",
        "Mozilla/5.0 (X11; Linux x86_64; rv:129.0) Gecko/20100101 Firefox/129.0",
        "Mozilla/5.0 (X11; Linux x86_64; rv:128.0) Gecko/20100101 Firefox/128.0",
        "Mozilla/5.0 (X11; Linux x86_64; rv:127.0) Gecko/20100101 Firefox/127.0",
        "Mozilla/5.0 (X11; Linux x86_64; rv:126.0) Gecko/20100101 Firefox/126.0",
        "Mozilla/5.0 (X11; Linux x86_64; rv:125.0) Gecko/20100101 Firefox/125.0",
        # Macintosh Chrome
        "Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/130.0.0.0 Safari/537.36",
        "Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/129.0.0.0 Safari/537.36",
        "Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/128.0.0.0 Safari/537.36",
        "Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/127.0.0.0 Safari/537.36",
        "Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/126.0.0.0 Safari/537.36",
        "Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/125.0.0.0 Safari/537.36",
        # Macintosh Edge
        "Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/130.0.0.0 Safari/537.36 Edg/130.0.0.0",
        "Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/129.0.0.0 Safari/537.36 Edg/129.0.0.0",
        "Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/128.0.0.0 Safari/537.36 Edg/128.0.0.0",
        "Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/127.0.0.0 Safari/537.36 Edg/127.0.0.0",
        "Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/126.0.0.0 Safari/537.36 Edg/126.0.0.0",
        "Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/125.0.0.0 Safari/537.36 Edg/125.0.0.0",
        # Macintosh Firefox
        "Mozilla/5.0 (Macintosh; Intel Mac OS X 10.15; rv:130.0) Gecko/20100101 Firefox/130.0",
        "Mozilla/5.0 (Macintosh; Intel Mac OS X 10.15; rv:129.0) Gecko/20100101 Firefox/129.0",
        "Mozilla/5.0 (Macintosh; Intel Mac OS X 10.15; rv:128.0) Gecko/20100101 Firefox/128.0",
        "Mozilla/5.0 (Macintosh; Intel Mac OS X 10.15; rv:127.0) Gecko/20100101 Firefox/127.0",
        "Mozilla/5.0 (Macintosh; Intel Mac OS X 10.15; rv:126.0) Gecko/20100101 Firefox/126.0",
        "Mozilla/5.0 (Macintosh; Intel Mac OS X 10.15; rv:125.0) Gecko/20100101 Firefox/125.0",
        # Android Chrome
        "Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/130.0.0.0 Mobile Safari/537.3",
        "Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/129.0.0.0 Mobile Safari/537.3",
        "Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/128.0.0.0 Mobile Safari/537.3",
        "Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/127.0.0.0 Mobile Safari/537.3",
        "Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/126.0.0.0 Mobile Safari/537.3",
        "Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/125.0.0.0 Mobile Safari/537.3",
        # Android Edge
        "Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/130.0.0.0 Mobile Safari/537.36 EdgA/130.0.0.0",
        "Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/129.0.0.0 Mobile Safari/537.36 EdgA/129.0.0.0",
        "Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/128.0.0.0 Mobile Safari/537.36 EdgA/128.0.0.0",
        "Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/127.0.0.0 Mobile Safari/537.36 EdgA/127.0.0.0",
        "Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/126.0.0.0 Mobile Safari/537.36 EdgA/126.0.0.0",
        "Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/125.0.0.0 Mobile Safari/537.36 EdgA/125.0.0.0",
        # Android Firefox
        "Mozilla/5.0 (Android 15; Mobile; rv:130.0) Gecko/130.0 Firefox/130.0",
        "Mozilla/5.0 (Android 15; Mobile; rv:129.0) Gecko/129.0 Firefox/129.0",
        "Mozilla/5.0 (Android 15; Mobile; rv:128.0) Gecko/128.0 Firefox/128.0",
        "Mozilla/5.0 (Android 15; Mobile; rv:127.0) Gecko/127.0 Firefox/127.0",
        "Mozilla/5.0 (Android 15; Mobile; rv:126.0) Gecko/126.0 Firefox/126.0",
        "Mozilla/5.0 (Android 15; Mobile; rv:125.0) Gecko/125.0 Firefox/125.0"              # Sum = 72 UserAgents
    ]

    # Set up a BaseURL
    base_url = "https://example.com"
    links = [
        ["Διαχείριση Δικτύων 2018", "19/11/2018"],
        ["Διαχείριση Δικτύων 2018", "12/11/2018"],
        ["Διαχείριση Δικτύων 2018", "05/11/2018"],
        ["Διαχείριση Δικτύων 2018", "22/10/2018"],
        ["Διαχείριση Δικτύων 2018", "08/10/2018"],
        ["Διαχείριση Δικτύων 2018", "01/10/2018"],      # 06

        ["Διαχείριση Δικτύων 2017", "17/01/2018"],
        ["Διαχείριση Δικτύων 2017", "11/12/2017"],
        ["Διαχείριση Δικτύων 2017", "20/11/2017"],
        ["Διαχείριση Δικτύων 2017", "30/10/2017"],
        ["Διαχείριση Δικτύων 2017", "23/10/2017"],
        ["Διαχείριση Δικτύων 2017", "09/10/2017"],
        ["Διαχείριση Δικτύων 2017", "02/10/2017"],      # 07

        ["Συστήματα Αναμονής 2018", "06/06/2018"],
        ["Συστήματα Αναμονής 2018", "30/05/2018"],
        ["Συστήματα Αναμονής 2018", "23/05/2018"],
        ["Συστήματα Αναμονής 2018", "09/05/2018"],
        ["Συστήματα Αναμονής 2018", "02/05/2018"],
        ["Συστήματα Αναμονής 2018", "18/04/2018"],
        ["Συστήματα Αναμονής 2018", "28/03/2018"],
        ["Συστήματα Αναμονής 2018", "14/03/2018"],
        ["Συστήματα Αναμονής 2018", "21/02/2018"],      # 09

        ["Συστήματα Αναμονής 2017", "14/06/2017"],
        ["Συστήματα Αναμονής 2017", "07/06/2017"],
        ["Συστήματα Αναμονής 2017", "23/05/2017"],
        ["Συστήματα Αναμονής 2017", "10/05/2017"],
        ["Συστήματα Αναμονής 2017", "03/05/2017"],
        ["Συστήματα Αναμονής 2017", "26/04/2017"],
        ["Συστήματα Αναμονής 2017", "05/04/2017"],
        ["Συστήματα Αναμονής 2017", "29/03/2017"],
        ["Συστήματα Αναμονής 2017", "22/03/2017"],
        ["Συστήματα Αναμονής 2017", "15/03/2017"],
        ["Συστήματα Αναμονής 2017", "08/03/2017"],
        ["Συστήματα Αναμονής 2017", "01/03/2017"]       # 12        Sum = 34 pages
    ]

    # shuffled_UserAgents = random.sample(UserAgents, len(UserAgents))
    # Display the original and shuffled lists
    # if verboselogs: print("Original UserAgents:", UserAgents)
    # if verboselogs: print("Shuffled UserAgents:", shuffled_UserAgents)
    # UserAgent = random.sample(UserAgents, 1)
    # UserAgent = shuffled_UserAgents[int(key)]

    UserAgent = UserAgents[int(key)]
    browser = create_browser(UserAgent)
    print(f"Time2: {datetime.today().strftime('%Y/%m/%d %H:%M:%S')}:  Task {key}:  ChromeDriver_setup called.  UserAgent: '{UserAgent}'")

    # Perform page actions
    browser.get(base_url)
    WebDriverWait(browser, 1).until(EC.presence_of_element_located((By.ID, "site-content")))
    if verboselogs: print(f"Time3: {datetime.today().strftime('%Y/%m/%d %H:%M:%S')}:  Task {key}:  Title: '{browser.title}'  UserAgent: '{UserAgent}'")
    # rand1 = random.sample(range(1, 12), 1)
    # if verboselogs: print(f"len(links): {len(links)}")
    rand1 = [random.randint(0, len(links)-1)]
    # if verboselogs: print(f"rand1: {rand1}")
    for p in rand1:
        actions1 = ActionChains(browser)
        element1 = browser.find_element(By.PARTIAL_LINK_TEXT, links[p][0])
        actions1.move_to_element(element1).perform()
        actions1.click().perform()
        if verboselogs: print(f"Time4: {datetime.today().strftime('%Y/%m/%d %H:%M:%S')}:  Task {key}:  Title: '{browser.title}'  UserAgent: '{UserAgent}'")
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
        # browser.get(url)

        # Pause for the page to load
        time.sleep(2)
        if verboselogs: print(f"Time5: {datetime.today().strftime('%Y/%m/%d %H:%M:%S')}:  Task {key}:  Title: '{browser.title}'  UserAgent: '{UserAgent}'")
        time.sleep(random.randint(10,20))
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
        if verboselogs: print(f"Time6: {datetime.today().strftime('%Y/%m/%d %H:%M:%S')}:  Task {key}:  Title: '{browser.title}'  UserAgent: '{UserAgent}'")

    finally:
        browser.quit()                                  # Close the browser

if __name__ == "__main__":
    # Define the URLs and keys to send
    # ints = [random.randint(0,9)]
    pages = [{"url": f"{l:02}", "key": f"{l:02}"} for l in range(1, 73)]
    # if verboselogs: print(f"pages: {pages} len(pages): {len(pages)}")
    print(f"Time0: {datetime.today().strftime('%Y/%m/%d %H:%M:%S')}:  Starting scraping with multiprocessing ...")

    start = time.time()
    # Select N random pages, num_processes number of processes
    processes = []
    num_processes = 20
    random_pages = random.sample(pages, num_processes)
    if num_processes > len(pages):
        raise ValueError("Sample size must not be larger than the data size.")

    # Create a process for each page
    for page in random_pages:
        process = Process(target=send_keys_to_page, args=(page["url"], page["key"]))
        processes.append(process)
        process.start()

    # Wait for all processes to finish
    for process in processes:
        process.join()

    end = time.time()
    print(f"Time7: {datetime.today().strftime('%Y/%m/%d %H:%M:%S')}:  Ending scraping with multiprocessing ...")
    print(f"Time for scraping with multiprocessing: {round(end-start,2)} seconds")
    print(f"All tasks completed.")