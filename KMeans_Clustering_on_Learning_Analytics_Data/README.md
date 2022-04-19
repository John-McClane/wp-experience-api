# Machine Learning used to analyse Learning Analytics with K-Means Clustering

**John McClane, Zep Addicted**<br>
*NTUA*

**April 2022**

## Introduction
<p>
    In this article, we will explore K-Means Clustering:<br>
    <ul>
        <li><a href="#definition"> What is K-Means Clustering? </li>
        <li><a href="#algorithm">Algorithm</a></li>
        <li><a href="#application">K-Means Clustering Application: Analysing Learning Analytics</a></li>
    </ul>
</p>

Jupyter Notebooks are available on [Github](https://github.com/John-McClane/wp-experience-api).

For this project, we use several Python-based scientific computing technologies listed below.


```python
import kneed
import requests
import numpy as np
import pandas as pd
from pandas import DataFrame
from pandas import json_normalize
from tqdm import tqdm
from time import time
import seaborn as sns
import ipywidgets as widgets
from scipy.stats import mstats
import matplotlib.pyplot as plt
from sklearn.cluster import KMeans
from datetime import datetime, timedelta
from requests.adapters import HTTPAdapter
from requests.exceptions import ConnectionError
from requests.packages.urllib3.util.retry import Retry

import sys
import os
import pymongo
from ssh_pymongo import MongoSession
import logging
from IPython.core.interactiveshell import InteractiveShell
InteractiveShell.ast_node_interactivity = "all"

# Display progress logs on stdout
logging.basicConfig(level=logging.INFO, format="%(asctime)s %(levelname)s %(message)s")
```

<a name="definition"></a>
<h2><span>What is K-Means Clustering?  </span></h2>
<p>
K-Means Clustering is a form of unsupervised <a href="https://hdonnelly6.medium.com/list/machine-learning-for-investing-7f2690bb1826">machine learning</a> (ML). It is considered to be one of the simplest and most popular unsupervised machine learning techniques.
Unsupervised algorithms use vectors on data points. These data points are not labeled or classified. Our goal is to discover hidden patterns and group the data points in a sensible way based on similarity of features. Each group of data points is a cluster and each cluster will have a center.

#### K-Means

<img src="img/k_means.png">

<a href="https://www.analyticsvidhya.com/blog/2021/04/k-means-clustering-simplified-in-python/">Source : Analytics Vidhya </a>

### Examples

Let's imagine you have two dimensional data that is not labeled as shown below and you are asked to form clusters.

#### Row data

<img src="img/k_means_row_data.PNG">

Below is an example of good clustering.

#### Good Clustering

<img src="img/k_means_good_clustering.PNG" >

However, clustering can go wrong as seen below.

#### Naive Clustering

<img src="img/k_means_bad_clustering.PNG" >

Source: Oreilly.com : Clustering and Unsupervised Learning

<a name="algorithm"></a>
<h2><span>Algorithm </span></h2>

* Pre-process the data (Clean it, Scale it, Standardize it)
* Select K
* Pick K Centers
* Repeat until there is no change of the centroid positions: <BR>
   1) Compute the distance between data point (vector x) and all centroids. (Generally, we use the euclidean distance) <BR>
    <img src="img/k_means_euclidean.png" >
   2) Assign each data point to the closest cluster (centroid) <BR>
    <img src="img/k_means_argmin.png" >
   3) Compute the centroids for the clusters by taking the average of all data points that belong to each cluster.


 <img src="img/k_means_algorithm.PNG"  width="50%" > <BR>
 <img src="img/k_means_algorithm_gif.gif">


Training examples are shown as dots, and cluster centroids are shown as crosses.
    <ul>
        (a) Original dataset.<br>
        (b) Random initial cluster centroids.<br>
        (c-f) Illustration of running two iterations of k-means.<br>
    </ul>
In each iteration, we assign each training example to the closest cluster centroid (shown by "painting" the training examples the same color as the cluster centroid to which it is assigned). Then we move each cluster centroid to the mean of the points assigned to it.

<a href="https://stanford.edu/~cpiech/cs221/handouts/kmeans.html">Source : Stanford Edu ( K-Means) </a>


<a name="application"></a>
<h2><span>K-Means Clustering Application: Analysing Learning Analytics </span></h2>

We are going to use K-Means Clustering to analyse Learning Analytics Data. <BR>
<ul>
    <li><code>Learning Locker LRS:</code> Learning Locker LRS is used to capture Learning Analytics Data.</li>
    <li><code>Analysis:</code> The data is stored in MongoDB in the LRS and retrieved to perform analysis.</li>

</ul>
The idea is to create clusters with similar characteristics for the variables of the Learning Analytics data.<br><br>

Please note that this analysis is done using only two factors which leads to a two dimensional problem. We are using a two dimensional problem to demonstrate the concept and understand the problem. Multiple factors can be used as well. If you want to use multiple factors, you may want to use <a href="https://scikit-learn.org/stable/modules/generated/sklearn.decomposition.PCA.html">Principal Component Analysis</a> to lower the number of dimensions. <br><br>

We will proceed with the following steps: <br><b>K-Means Clustering : </b><br>

    1. Get the data: Connect to Remote Host to retrieve MongoDB data regarding Learning Analytics captured using Learning Locker LRS.
    2. Analyze the data, clean it and visualize it.
    3. Choose K.
    4. Analyze the clustering results.
<b>Risk Assesment: </b><br>

    1. From each cluster, choose the variables with the highest risk adjusted momentum.
    2. Run the data return prediction for next time period.

----
Let's apply the steps defined above:
## K-Means Clustering
### <I>1. Get the data: Connect to Remote Machine and retrieve MongoDB LL LRS Learning Analytics Data</I>


```python
# #############################################################################
# Set Debug Level. Silent = 0, Info = 1, Debug = 2
debug = 2

# #############################################################################
# Fetch MongoDB LL LRS Learning Analytics Data from Remote Machine
# Read MongoSessionCredentials from file
# MongoSessionCredentials.txt file format should be: host (192.168.2.2), user, key (full path /home/id_rsa), key_password (Key_P@ssword), uri (mongodb://127.0.0.1:27017). Separated by newline. No "", no ''.
filepath = "MongoSessionCredentials.txt"
if not os.path.isfile(filepath):
   print("File path {} does not exist. Exiting...".format(filepath))
   raise Exception("Exiting")
with open(filepath) as fp :
    mylist = fp.read().splitlines()
    mhost = mylist[0]
    muser = mylist[1]
    mkey = mylist[2]
    mkeypas = mylist[3]
    muri = mylist[4]
if debug > 1 :
    print("MongoSessionCredentials: mhost:",mhost,"muser:",muser,"mkey:",mkey,"mkeypas:",mkeypas,"muri:",muri,"\n")
    print("MongoSessionCredentials")
    mhost,muser,mkey,mkeypas,muri

# #############################################################################
# Initiate a Connection Session to Remote Machine and MongoDB
session = MongoSession(
    host=mhost,
    user=muser,
    key=mkey,
    key_password=mkeypas,
    uri=muri
)

#retry_strategy = Retry(total=3, backoff_factor=10, status_forcelist=[429, 500, 502, 503, 504], allowed_methods=["HEAD", "GET", "PUT", "DELETE", "OPTIONS", "TRACE"])
data = []
lldb = []

try:
    # Connect to Remote Machine and MongoDB Learning Locker LRS Collection "statement"
    lldb = session.connection['learninglocker_v2'] #(max_retries=retry_strategy)
    print("\nDataBase Connection Info:\n", lldb, "\n")
    if debug > 0 :
        print("\nCollections Names List:\n",lldb.list_collection_names(include_system_collections=False),"\n")
    # Get Learning Analytics statements from MongoDB Learning Locker LRS from collection "statement"
    statements_coll = lldb.statements
    print("\nCollection Statements:\n",statements_coll, "\n")
    t0=time()
    print("\n########## Execution Time: %0.3fs" % (time() - t0), "\n")

except requests.exceptions.RequestException as err:
    print ("Oops: Something Else",err)
except requests.exceptions.HTTPError as errh:
    print ("HTTP Error:",errh)
except requests.exceptions.ConnectionError as errc:
    print ("Error Connecting:",errc)
except requests.exceptions.Timeout as errt:
    print ("Timeout Error:",errt)
except:
    pass

# #############################################################################
# Convert data to DataFrame
df = json_normalize(list(
statements_coll.aggregate([
#        {
#            "$match": query
#        },
        {
            "$replaceRoot": {
                "newRoot": "$statement"
            }
        },
        { "$sort": { "stored": -1 } },
        { "$limit": 30 }
    ])
))

if debug > 0 :
    print("Type of df:",type(df))

if debug > 2 :
    # Printing the df to console
    print(df)

session.stop()
t1=time()
print("\n########## Execution Time: %0.3fs" % (time() - t1), "\n")

data = df
if debug > 0 :
    print ("\nData Length")
    len(data)
if debug > 0 :
    print ("\nData Shape")
    data.shape
if debug > 0 :
    print ("\nData Description")
    data.describe()
if debug > 1 :
    print ("\nData")
    data
```

    MongoSessionCredentials


    2022-04-19 12:24:04,584| ERROR   | Password is required for key id_rsa
    2022-04-19 12:24:04,584 ERROR Password is required for key id_rsa
    2022-04-19 12:24:04,668 INFO Connected (version 2.0, client OpenSSH_7.6p1)
    2022-04-19 12:24:05,573 INFO Authentication (publickey) successful!



    DataBase Connection Info:
     Database(MongoClient(host=['127.0.0.1:35015'], document_class=dict, tz_aware=False, connect=True), 'learninglocker_v2')


    Collections Names List:
     ['importcsv', 'personaAttributes', 'dashboards', 'migrations', 'personasImportTemplates', 'queryBuilderCacheValues', 'statements', 'lrs', 'batchDelete', 'orgUsageStats', 'exports', 'client', 'queries', 'users', 'queryBuilderCaches', 'statementForwarding', 'personaIdentifiers', 'organisations', 'downloads', 'personasImports', 'siteSettings', 'states', 'oAuthTokens', 'personas', 'role', 'fullActivities', 'importPersonasLock', 'streams', 'visualisations', 'importdata']


    Collection Statements:
     Collection(Database(MongoClient(host=['127.0.0.1:35015'], document_class=dict, tz_aware=False, connect=True), 'learninglocker_v2'), 'statements')


    ########## Execution Time: 0.000s

    Type of df: <class 'pandas.core.frame.DataFrame'>

    ########## Execution Time: 0.000s


    Data Length





    30




    Data Shape





    (30, 21)




    Data Description





<div>
<style scoped>
    .dataframe tbody tr th:only-of-type {
        vertical-align: middle;
    }

    .dataframe tbody tr th {
        vertical-align: top;
    }

    .dataframe thead th {
        text-align: right;
    }
</style>
<table border="1" class="dataframe">
  <thead>
    <tr style="text-align: right;">
      <th></th>
      <th>timestamp</th>
      <th>id</th>
      <th>stored</th>
      <th>version</th>
      <th>actor.objectType</th>
      <th>actor.name</th>
      <th>actor.mbox</th>
      <th>verb.id</th>
      <th>verb.display.en-US</th>
      <th>context.platform</th>
      <th>...</th>
      <th>context.extensions.http://id&amp;46;tincanapi&amp;46;com/extension/browser-info.user_agent</th>
      <th>context.extensions.http://id&amp;46;tincanapi&amp;46;com/extension/referrer</th>
      <th>object.objectType</th>
      <th>object.id</th>
      <th>object.definition.type</th>
      <th>object.definition.name.en-US</th>
      <th>object.definition.description.en-US</th>
      <th>authority.objectType</th>
      <th>authority.name</th>
      <th>authority.mbox</th>
    </tr>
  </thead>
  <tbody>
    <tr>
      <th>count</th>
      <td>30</td>
      <td>30</td>
      <td>30</td>
      <td>30</td>
      <td>30</td>
      <td>30</td>
      <td>30</td>
      <td>30</td>
      <td>30</td>
      <td>30</td>
      <td>...</td>
      <td>30</td>
      <td>30</td>
      <td>30</td>
      <td>30</td>
      <td>30</td>
      <td>30</td>
      <td>30</td>
      <td>30</td>
      <td>30</td>
      <td>30</td>
    </tr>
    <tr>
      <th>unique</th>
      <td>30</td>
      <td>30</td>
      <td>30</td>
      <td>1</td>
      <td>1</td>
      <td>28</td>
      <td>28</td>
      <td>1</td>
      <td>1</td>
      <td>1</td>
      <td>...</td>
      <td>24</td>
      <td>2</td>
      <td>1</td>
      <td>10</td>
      <td>1</td>
      <td>8</td>
      <td>1</td>
      <td>1</td>
      <td>1</td>
      <td>1</td>
    </tr>
    <tr>
      <th>top</th>
      <td>2021-12-29T04:39:20+00:00</td>
      <td>e7048d33-82cc-4d60-b29f-14d604203d53</td>
      <td>2021-12-29T04:39:25.224Z</td>
      <td>1.0.0</td>
      <td>Agent</td>
      <td>Guest.109.168.97.89</td>
      <td>mailto:guest.109.168.97.89@example.com</td>
      <td>http://id.tincanapi.com/verb/viewed</td>
      <td>viewed</td>
      <td>Unknown</td>
      <td>...</td>
      <td>Mozilla/5.0 (compatible; SemrushBot/7~bl; +htt...</td>
      <td></td>
      <td>Activity</td>
      <td>https://example.com/wp-login.php</td>
      <td>http://activitystrea.ms/schema/1.0/page</td>
      <td></td>
      <td>Viewed Page</td>
      <td>Agent</td>
      <td>NTUA NetMODe</td>
      <td>mailto:info@example.com</td>
    </tr>
    <tr>
      <th>freq</th>
      <td>1</td>
      <td>1</td>
      <td>1</td>
      <td>30</td>
      <td>30</td>
      <td>2</td>
      <td>2</td>
      <td>30</td>
      <td>30</td>
      <td>30</td>
      <td>...</td>
      <td>3</td>
      <td>29</td>
      <td>30</td>
      <td>21</td>
      <td>30</td>
      <td>22</td>
      <td>30</td>
      <td>30</td>
      <td>30</td>
      <td>30</td>
    </tr>
  </tbody>
</table>
<p>4 rows × 21 columns</p>
</div>




    Data





<div>
<style scoped>
    .dataframe tbody tr th:only-of-type {
        vertical-align: middle;
    }

    .dataframe tbody tr th {
        vertical-align: top;
    }

    .dataframe thead th {
        text-align: right;
    }
</style>
<table border="1" class="dataframe">
  <thead>
    <tr style="text-align: right;">
      <th></th>
      <th>timestamp</th>
      <th>id</th>
      <th>stored</th>
      <th>version</th>
      <th>actor.objectType</th>
      <th>actor.name</th>
      <th>actor.mbox</th>
      <th>verb.id</th>
      <th>verb.display.en-US</th>
      <th>context.platform</th>
      <th>...</th>
      <th>context.extensions.http://id&amp;46;tincanapi&amp;46;com/extension/browser-info.user_agent</th>
      <th>context.extensions.http://id&amp;46;tincanapi&amp;46;com/extension/referrer</th>
      <th>object.objectType</th>
      <th>object.id</th>
      <th>object.definition.type</th>
      <th>object.definition.name.en-US</th>
      <th>object.definition.description.en-US</th>
      <th>authority.objectType</th>
      <th>authority.name</th>
      <th>authority.mbox</th>
    </tr>
  </thead>
  <tbody>
    <tr>
      <th>0</th>
      <td>2021-12-29T04:39:20+00:00</td>
      <td>e7048d33-82cc-4d60-b29f-14d604203d53</td>
      <td>2021-12-29T04:39:25.224Z</td>
      <td>1.0.0</td>
      <td>Agent</td>
      <td>Guest.115.29.199.218</td>
      <td>mailto:guest.115.29.199.218@example.com</td>
      <td>http://id.tincanapi.com/verb/viewed</td>
      <td>viewed</td>
      <td>Unknown</td>
      <td>...</td>
      <td>Mozilla/5.0 (Windows NT 6.1; Win64; x64) Apple...</td>
      <td></td>
      <td>Activity</td>
      <td>https://example.com/wp-login.php</td>
      <td>http://activitystrea.ms/schema/1.0/page</td>
      <td></td>
      <td>Viewed Page</td>
      <td>Agent</td>
      <td>NTUA NetMODe</td>
      <td>mailto:info@example.com</td>
    </tr>
    <tr>
      <th>1</th>
      <td>2021-12-29T04:23:58+00:00</td>
      <td>1de0f462-f2d5-4822-a246-c07a925eb447</td>
      <td>2021-12-29T04:23:58.246Z</td>
      <td>1.0.0</td>
      <td>Agent</td>
      <td>Guest.212.103.4.29</td>
      <td>mailto:guest.212.103.4.29@example.com</td>
      <td>http://id.tincanapi.com/verb/viewed</td>
      <td>viewed</td>
      <td>Unknown</td>
      <td>...</td>
      <td>Mozilla/5.0 (Windows NT 10.0; Win64; x64) Appl...</td>
      <td></td>
      <td>Activity</td>
      <td>https://example.com/wp-login.php</td>
      <td>http://activitystrea.ms/schema/1.0/page</td>
      <td></td>
      <td>Viewed Page</td>
      <td>Agent</td>
      <td>NTUA NetMODe</td>
      <td>mailto:info@example.com</td>
    </tr>
    <tr>
      <th>2</th>
      <td>2021-12-29T04:08:55+00:00</td>
      <td>6a984cb0-92ff-48a1-a632-b77f53494390</td>
      <td>2021-12-29T04:08:55.557Z</td>
      <td>1.0.0</td>
      <td>Agent</td>
      <td>Guest.188.166.152.29</td>
      <td>mailto:guest.188.166.152.29@example.com</td>
      <td>http://id.tincanapi.com/verb/viewed</td>
      <td>viewed</td>
      <td>Unknown</td>
      <td>...</td>
      <td>Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKi...</td>
      <td></td>
      <td>Activity</td>
      <td>https://example.com/wp-login.php</td>
      <td>http://activitystrea.ms/schema/1.0/page</td>
      <td></td>
      <td>Viewed Page</td>
      <td>Agent</td>
      <td>NTUA NetMODe</td>
      <td>mailto:info@example.com</td>
    </tr>
    <tr>
      <th>3</th>
      <td>2021-12-29T03:53:54+00:00</td>
      <td>bc89786b-5544-4597-a1ef-7ee665a53e2f</td>
      <td>2021-12-29T03:53:54.498Z</td>
      <td>1.0.0</td>
      <td>Agent</td>
      <td>Guest.35.241.177.128</td>
      <td>mailto:guest.35.241.177.128@example.com</td>
      <td>http://id.tincanapi.com/verb/viewed</td>
      <td>viewed</td>
      <td>Unknown</td>
      <td>...</td>
      <td>Mozilla/5.0 (Windows NT 6.1; Win64; x64) Apple...</td>
      <td></td>
      <td>Activity</td>
      <td>https://example.com/wp-login.php</td>
      <td>http://activitystrea.ms/schema/1.0/page</td>
      <td></td>
      <td>Viewed Page</td>
      <td>Agent</td>
      <td>NTUA NetMODe</td>
      <td>mailto:info@example.com</td>
    </tr>
    <tr>
      <th>4</th>
      <td>2021-12-29T03:38:45+00:00</td>
      <td>d3bd2bfd-3350-4b23-9d22-37d447a08a47</td>
      <td>2021-12-29T03:38:45.230Z</td>
      <td>1.0.0</td>
      <td>Agent</td>
      <td>Guest.109.168.97.89</td>
      <td>mailto:guest.109.168.97.89@example.com</td>
      <td>http://id.tincanapi.com/verb/viewed</td>
      <td>viewed</td>
      <td>Unknown</td>
      <td>...</td>
      <td>Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKi...</td>
      <td></td>
      <td>Activity</td>
      <td>https://example.com/wp-login.php</td>
      <td>http://activitystrea.ms/schema/1.0/page</td>
      <td></td>
      <td>Viewed Page</td>
      <td>Agent</td>
      <td>NTUA NetMODe</td>
      <td>mailto:info@example.com</td>
    </tr>
    <tr>
      <th>5</th>
      <td>2021-12-29T03:23:35+00:00</td>
      <td>48033410-76a9-4e4c-b098-9c867d5de538</td>
      <td>2021-12-29T03:23:35.118Z</td>
      <td>1.0.0</td>
      <td>Agent</td>
      <td>Guest.65.21.234.156</td>
      <td>mailto:guest.65.21.234.156@example.com</td>
      <td>http://id.tincanapi.com/verb/viewed</td>
      <td>viewed</td>
      <td>Unknown</td>
      <td>...</td>
      <td>Mozilla/5.0 (Windows NT 6.1) AppleWebKit/537.3...</td>
      <td></td>
      <td>Activity</td>
      <td>https://example.com/wp-login.php</td>
      <td>http://activitystrea.ms/schema/1.0/page</td>
      <td></td>
      <td>Viewed Page</td>
      <td>Agent</td>
      <td>NTUA NetMODe</td>
      <td>mailto:info@example.com</td>
    </tr>
    <tr>
      <th>6</th>
      <td>2021-12-29T02:52:57+00:00</td>
      <td>27c2cd27-b061-469c-ad77-1e89a1585056</td>
      <td>2021-12-29T02:52:57.445Z</td>
      <td>1.0.0</td>
      <td>Agent</td>
      <td>Guest.109.168.97.89</td>
      <td>mailto:guest.109.168.97.89@example.com</td>
      <td>http://id.tincanapi.com/verb/viewed</td>
      <td>viewed</td>
      <td>Unknown</td>
      <td>...</td>
      <td>Mozilla/5.0 (Windows NT 6.3; Win64; x64) Apple...</td>
      <td></td>
      <td>Activity</td>
      <td>https://example.com/wp-login.php</td>
      <td>http://activitystrea.ms/schema/1.0/page</td>
      <td></td>
      <td>Viewed Page</td>
      <td>Agent</td>
      <td>NTUA NetMODe</td>
      <td>mailto:info@example.com</td>
    </tr>
    <tr>
      <th>7</th>
      <td>2021-12-29T02:50:17+00:00</td>
      <td>d684ce9e-4bbf-478d-bc8f-bc8a70e630e5</td>
      <td>2021-12-29T02:50:17.697Z</td>
      <td>1.0.0</td>
      <td>Agent</td>
      <td>Guest.207.46.13.197</td>
      <td>mailto:guest.207.46.13.197@example.com</td>
      <td>http://id.tincanapi.com/verb/viewed</td>
      <td>viewed</td>
      <td>Unknown</td>
      <td>...</td>
      <td>Mozilla/5.0 (compatible; bingbot/2.0; +http://...</td>
      <td></td>
      <td>Activity</td>
      <td>https://example.com/courses/?lecture...</td>
      <td>http://activitystrea.ms/schema/1.0/page</td>
      <td>Διαχείριση Δικτύων – Ευφυή Δίκτυα, 9ο Εξάμηνο,...</td>
      <td>Viewed Page</td>
      <td>Agent</td>
      <td>NTUA NetMODe</td>
      <td>mailto:info@example.com</td>
    </tr>
    <tr>
      <th>8</th>
      <td>2021-12-29T02:22:20+00:00</td>
      <td>404fb2f5-ba7d-4463-a699-0de42d7037bb</td>
      <td>2021-12-29T02:22:20.463Z</td>
      <td>1.0.0</td>
      <td>Agent</td>
      <td>Guest.107.172.82.148</td>
      <td>mailto:guest.107.172.82.148@example.com</td>
      <td>http://id.tincanapi.com/verb/viewed</td>
      <td>viewed</td>
      <td>Unknown</td>
      <td>...</td>
      <td>Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US...</td>
      <td></td>
      <td>Activity</td>
      <td>https://example.com/wp-login.php</td>
      <td>http://activitystrea.ms/schema/1.0/page</td>
      <td></td>
      <td>Viewed Page</td>
      <td>Agent</td>
      <td>NTUA NetMODe</td>
      <td>mailto:info@example.com</td>
    </tr>
    <tr>
      <th>9</th>
      <td>2021-12-29T02:06:55+00:00</td>
      <td>b2cf9f12-214b-45a9-86fb-ad24053b54c2</td>
      <td>2021-12-29T02:06:55.439Z</td>
      <td>1.0.0</td>
      <td>Agent</td>
      <td>Guest.173.212.235.115</td>
      <td>mailto:guest.173.212.235.115@example.com</td>
      <td>http://id.tincanapi.com/verb/viewed</td>
      <td>viewed</td>
      <td>Unknown</td>
      <td>...</td>
      <td>Mozilla/5.0 (Windows NT 10.0; Win64; x64) Appl...</td>
      <td></td>
      <td>Activity</td>
      <td>https://example.com/wp-login.php</td>
      <td>http://activitystrea.ms/schema/1.0/page</td>
      <td></td>
      <td>Viewed Page</td>
      <td>Agent</td>
      <td>NTUA NetMODe</td>
      <td>mailto:info@example.com</td>
    </tr>
    <tr>
      <th>10</th>
      <td>2021-12-29T01:51:38+00:00</td>
      <td>25e6f0a0-2d22-48f6-9776-4c35d664b821</td>
      <td>2021-12-29T01:51:43.095Z</td>
      <td>1.0.0</td>
      <td>Agent</td>
      <td>Guest.157.245.217.209</td>
      <td>mailto:guest.157.245.217.209@example.com</td>
      <td>http://id.tincanapi.com/verb/viewed</td>
      <td>viewed</td>
      <td>Unknown</td>
      <td>...</td>
      <td>Mozilla/5.0 (Windows NT 6.1; Win64; x64) Apple...</td>
      <td></td>
      <td>Activity</td>
      <td>https://example.com/wp-login.php</td>
      <td>http://activitystrea.ms/schema/1.0/page</td>
      <td></td>
      <td>Viewed Page</td>
      <td>Agent</td>
      <td>NTUA NetMODe</td>
      <td>mailto:info@example.com</td>
    </tr>
    <tr>
      <th>11</th>
      <td>2021-12-29T01:36:18+00:00</td>
      <td>becefa60-19b7-49cd-91b9-66fccbc4e330</td>
      <td>2021-12-29T01:36:18.724Z</td>
      <td>1.0.0</td>
      <td>Agent</td>
      <td>Guest.161.35.94.99</td>
      <td>mailto:guest.161.35.94.99@example.com</td>
      <td>http://id.tincanapi.com/verb/viewed</td>
      <td>viewed</td>
      <td>Unknown</td>
      <td>...</td>
      <td>Mozilla/5.0 (Windows NT 6.1) AppleWebKit/537.3...</td>
      <td></td>
      <td>Activity</td>
      <td>https://example.com/wp-login.php</td>
      <td>http://activitystrea.ms/schema/1.0/page</td>
      <td></td>
      <td>Viewed Page</td>
      <td>Agent</td>
      <td>NTUA NetMODe</td>
      <td>mailto:info@example.com</td>
    </tr>
    <tr>
      <th>12</th>
      <td>2021-12-29T01:21:19+00:00</td>
      <td>441a84c2-8471-47ad-8cf2-0189df8bec00</td>
      <td>2021-12-29T01:21:19.502Z</td>
      <td>1.0.0</td>
      <td>Agent</td>
      <td>Guest.34.96.130.6</td>
      <td>mailto:guest.34.96.130.6@example.com</td>
      <td>http://id.tincanapi.com/verb/viewed</td>
      <td>viewed</td>
      <td>Unknown</td>
      <td>...</td>
      <td>Expanse indexes the network perimeters of our ...</td>
      <td></td>
      <td>Activity</td>
      <td>https://example.com/</td>
      <td>http://activitystrea.ms/schema/1.0/page</td>
      <td>Διαλέξεις Μαθημάτων NETMODE</td>
      <td>Viewed Page</td>
      <td>Agent</td>
      <td>NTUA NetMODe</td>
      <td>mailto:info@example.com</td>
    </tr>
    <tr>
      <th>13</th>
      <td>2021-12-29T01:05:47+00:00</td>
      <td>34b69e43-d137-4d85-9fb7-6dc9723ee9c9</td>
      <td>2021-12-29T01:05:47.067Z</td>
      <td>1.0.0</td>
      <td>Agent</td>
      <td>Guest.188.166.236.240</td>
      <td>mailto:guest.188.166.236.240@example.com</td>
      <td>http://id.tincanapi.com/verb/viewed</td>
      <td>viewed</td>
      <td>Unknown</td>
      <td>...</td>
      <td>Mozilla/5.0 (Windows NT 10.0; WOW64) AppleWebK...</td>
      <td></td>
      <td>Activity</td>
      <td>https://example.com/wp-login.php</td>
      <td>http://activitystrea.ms/schema/1.0/page</td>
      <td></td>
      <td>Viewed Page</td>
      <td>Agent</td>
      <td>NTUA NetMODe</td>
      <td>mailto:info@example.com</td>
    </tr>
    <tr>
      <th>14</th>
      <td>2021-12-29T00:50:29+00:00</td>
      <td>0f90c9c5-1bf6-4117-90bf-585a3ea96f2f</td>
      <td>2021-12-29T00:50:29.976Z</td>
      <td>1.0.0</td>
      <td>Agent</td>
      <td>Guest.128.199.242.105</td>
      <td>mailto:guest.128.199.242.105@example.com</td>
      <td>http://id.tincanapi.com/verb/viewed</td>
      <td>viewed</td>
      <td>Unknown</td>
      <td>...</td>
      <td>Mozilla/5.0 (Windows NT 6.1; Win64; x64) Apple...</td>
      <td></td>
      <td>Activity</td>
      <td>https://example.com/wp-login.php</td>
      <td>http://activitystrea.ms/schema/1.0/page</td>
      <td></td>
      <td>Viewed Page</td>
      <td>Agent</td>
      <td>NTUA NetMODe</td>
      <td>mailto:info@example.com</td>
    </tr>
    <tr>
      <th>15</th>
      <td>2021-12-29T00:34:38+00:00</td>
      <td>6f0d3e96-560b-4b84-9e0e-283ce5333a49</td>
      <td>2021-12-29T00:34:38.655Z</td>
      <td>1.0.0</td>
      <td>Agent</td>
      <td>Guest.77.88.5.245</td>
      <td>mailto:guest.77.88.5.245@example.com</td>
      <td>http://id.tincanapi.com/verb/viewed</td>
      <td>viewed</td>
      <td>Unknown</td>
      <td>...</td>
      <td>Mozilla/5.0 (compatible; YandexBot/3.0; +http:...</td>
      <td></td>
      <td>Activity</td>
      <td>https://example.com/courses/?lecture...</td>
      <td>http://activitystrea.ms/schema/1.0/page</td>
      <td>Network Management &amp;#8211; Intelligent Network...</td>
      <td>Viewed Page</td>
      <td>Agent</td>
      <td>NTUA NetMODe</td>
      <td>mailto:info@example.com</td>
    </tr>
    <tr>
      <th>16</th>
      <td>2021-12-29T00:20:01+00:00</td>
      <td>ac9c2973-05cc-4cf7-b9f6-8d8bb286aee8</td>
      <td>2021-12-29T00:20:01.359Z</td>
      <td>1.0.0</td>
      <td>Agent</td>
      <td>Guest.133.130.102.247</td>
      <td>mailto:guest.133.130.102.247@example.com</td>
      <td>http://id.tincanapi.com/verb/viewed</td>
      <td>viewed</td>
      <td>Unknown</td>
      <td>...</td>
      <td>Mozilla/5.0 (Windows NT 10.0; Win64; x64) Appl...</td>
      <td></td>
      <td>Activity</td>
      <td>https://example.com/wp-login.php</td>
      <td>http://activitystrea.ms/schema/1.0/page</td>
      <td></td>
      <td>Viewed Page</td>
      <td>Agent</td>
      <td>NTUA NetMODe</td>
      <td>mailto:info@example.com</td>
    </tr>
    <tr>
      <th>17</th>
      <td>2021-12-29T00:07:15+00:00</td>
      <td>4c948a67-63ff-4c03-b72f-2b1a950e58db</td>
      <td>2021-12-29T00:07:15.472Z</td>
      <td>1.0.0</td>
      <td>Agent</td>
      <td>Guest.69.30.240.28</td>
      <td>mailto:guest.69.30.240.28@example.com</td>
      <td>http://id.tincanapi.com/verb/viewed</td>
      <td>viewed</td>
      <td>Unknown</td>
      <td>...</td>
      <td>Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKi...</td>
      <td>http://www.google.com.hk</td>
      <td>Activity</td>
      <td>https://example.com//xmlrpc.php</td>
      <td>http://activitystrea.ms/schema/1.0/page</td>
      <td></td>
      <td>Viewed Page</td>
      <td>Agent</td>
      <td>NTUA NetMODe</td>
      <td>mailto:info@example.com</td>
    </tr>
    <tr>
      <th>18</th>
      <td>2021-12-29T00:04:42+00:00</td>
      <td>c969f849-3f1b-4099-829c-ab1ee7790aca</td>
      <td>2021-12-29T00:04:42.728Z</td>
      <td>1.0.0</td>
      <td>Agent</td>
      <td>Guest.173.255.112.220</td>
      <td>mailto:guest.173.255.112.220@example.com</td>
      <td>http://id.tincanapi.com/verb/viewed</td>
      <td>viewed</td>
      <td>Unknown</td>
      <td>...</td>
      <td>Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKi...</td>
      <td></td>
      <td>Activity</td>
      <td>https://example.com/wp-login.php</td>
      <td>http://activitystrea.ms/schema/1.0/page</td>
      <td></td>
      <td>Viewed Page</td>
      <td>Agent</td>
      <td>NTUA NetMODe</td>
      <td>mailto:info@example.com</td>
    </tr>
    <tr>
      <th>19</th>
      <td>2021-12-28T23:34:13+00:00</td>
      <td>ee513cdb-f6ae-47f7-bca1-3e0e5ba64a00</td>
      <td>2021-12-28T23:34:13.802Z</td>
      <td>1.0.0</td>
      <td>Agent</td>
      <td>Guest.95.217.3.203</td>
      <td>mailto:guest.95.217.3.203@example.com</td>
      <td>http://id.tincanapi.com/verb/viewed</td>
      <td>viewed</td>
      <td>Unknown</td>
      <td>...</td>
      <td>Mozilla/5.0 (Windows NT 6.1) AppleWebKit/537.3...</td>
      <td></td>
      <td>Activity</td>
      <td>https://example.com/wp-login.php</td>
      <td>http://activitystrea.ms/schema/1.0/page</td>
      <td></td>
      <td>Viewed Page</td>
      <td>Agent</td>
      <td>NTUA NetMODe</td>
      <td>mailto:info@example.com</td>
    </tr>
    <tr>
      <th>20</th>
      <td>2021-12-28T23:23:54+00:00</td>
      <td>94ed85d9-ead3-450d-aeb4-8f4a8f6003f5</td>
      <td>2021-12-28T23:23:54.763Z</td>
      <td>1.0.0</td>
      <td>Agent</td>
      <td>Guest.185.191.171.39</td>
      <td>mailto:guest.185.191.171.39@example.com</td>
      <td>http://id.tincanapi.com/verb/viewed</td>
      <td>viewed</td>
      <td>Unknown</td>
      <td>...</td>
      <td>Mozilla/5.0 (compatible; SemrushBot/7~bl; +htt...</td>
      <td></td>
      <td>Activity</td>
      <td>https://example.com/courses/?lecture...</td>
      <td>http://activitystrea.ms/schema/1.0/page</td>
      <td>Διαχείριση Δικτύων – Ευφυή Δίκτυα, 9ο Εξάμηνο,...</td>
      <td>Viewed Page</td>
      <td>Agent</td>
      <td>NTUA NetMODe</td>
      <td>mailto:info@example.com</td>
    </tr>
    <tr>
      <th>21</th>
      <td>2021-12-28T23:17:35+00:00</td>
      <td>7176c726-ca24-4734-88f9-7362e7ef9567</td>
      <td>2021-12-28T23:17:35.899Z</td>
      <td>1.0.0</td>
      <td>Agent</td>
      <td>Guest.77.88.5.44</td>
      <td>mailto:guest.77.88.5.44@example.com</td>
      <td>http://id.tincanapi.com/verb/viewed</td>
      <td>viewed</td>
      <td>Unknown</td>
      <td>...</td>
      <td>Mozilla/5.0 (compatible; YandexBot/3.0; +http:...</td>
      <td></td>
      <td>Activity</td>
      <td>https://example.com/courses/</td>
      <td>http://activitystrea.ms/schema/1.0/page</td>
      <td>Διαλέξεις Μαθημάτων NETMODE</td>
      <td>Viewed Page</td>
      <td>Agent</td>
      <td>NTUA NetMODe</td>
      <td>mailto:info@example.com</td>
    </tr>
    <tr>
      <th>22</th>
      <td>2021-12-28T23:03:48+00:00</td>
      <td>6cb279fc-359d-4b8d-8cb2-8bcef73b66ff</td>
      <td>2021-12-28T23:03:48.472Z</td>
      <td>1.0.0</td>
      <td>Agent</td>
      <td>Guest.46.101.150.34</td>
      <td>mailto:guest.46.101.150.34@example.com</td>
      <td>http://id.tincanapi.com/verb/viewed</td>
      <td>viewed</td>
      <td>Unknown</td>
      <td>...</td>
      <td>Mozilla/5.0 (Windows NT 6.1; Win64; x64) Apple...</td>
      <td></td>
      <td>Activity</td>
      <td>https://example.com/wp-login.php</td>
      <td>http://activitystrea.ms/schema/1.0/page</td>
      <td></td>
      <td>Viewed Page</td>
      <td>Agent</td>
      <td>NTUA NetMODe</td>
      <td>mailto:info@example.com</td>
    </tr>
    <tr>
      <th>23</th>
      <td>2021-12-28T22:48:33+00:00</td>
      <td>6c1dcdf3-6350-4555-8777-8e71995914ac</td>
      <td>2021-12-28T22:48:33.194Z</td>
      <td>1.0.0</td>
      <td>Agent</td>
      <td>Guest.159.65.76.38</td>
      <td>mailto:guest.159.65.76.38@example.com</td>
      <td>http://id.tincanapi.com/verb/viewed</td>
      <td>viewed</td>
      <td>Unknown</td>
      <td>...</td>
      <td>Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKi...</td>
      <td></td>
      <td>Activity</td>
      <td>https://example.com/wp-login.php</td>
      <td>http://activitystrea.ms/schema/1.0/page</td>
      <td></td>
      <td>Viewed Page</td>
      <td>Agent</td>
      <td>NTUA NetMODe</td>
      <td>mailto:info@example.com</td>
    </tr>
    <tr>
      <th>24</th>
      <td>2021-12-28T22:33:21+00:00</td>
      <td>fa16547d-ecd8-417d-a2e9-89916791b821</td>
      <td>2021-12-28T22:33:21.335Z</td>
      <td>1.0.0</td>
      <td>Agent</td>
      <td>Guest.207.180.213.165</td>
      <td>mailto:guest.207.180.213.165@example.com</td>
      <td>http://id.tincanapi.com/verb/viewed</td>
      <td>viewed</td>
      <td>Unknown</td>
      <td>...</td>
      <td>Mozilla/5.0 (Windows NT 6.1; Win64; x64) Apple...</td>
      <td></td>
      <td>Activity</td>
      <td>https://example.com/wp-login.php</td>
      <td>http://activitystrea.ms/schema/1.0/page</td>
      <td></td>
      <td>Viewed Page</td>
      <td>Agent</td>
      <td>NTUA NetMODe</td>
      <td>mailto:info@example.com</td>
    </tr>
    <tr>
      <th>25</th>
      <td>2021-12-28T22:26:35+00:00</td>
      <td>d3b01d9a-87b6-436b-af1f-cf5efe282651</td>
      <td>2021-12-28T22:26:35.336Z</td>
      <td>1.0.0</td>
      <td>Agent</td>
      <td>Guest.185.191.171.19</td>
      <td>mailto:guest.185.191.171.19@example.com</td>
      <td>http://id.tincanapi.com/verb/viewed</td>
      <td>viewed</td>
      <td>Unknown</td>
      <td>...</td>
      <td>Mozilla/5.0 (compatible; SemrushBot/7~bl; +htt...</td>
      <td></td>
      <td>Activity</td>
      <td>https://example.com/courses/?lecture...</td>
      <td>http://activitystrea.ms/schema/1.0/page</td>
      <td>Συστήματα Αναμονής, 6ο Εξάμηνο: Εισαγωγή (Μέρο...</td>
      <td>Viewed Page</td>
      <td>Agent</td>
      <td>NTUA NetMODe</td>
      <td>mailto:info@example.com</td>
    </tr>
    <tr>
      <th>26</th>
      <td>2021-12-28T22:14:12+00:00</td>
      <td>13cf6340-6df2-4c33-ae87-6abe820fe3e4</td>
      <td>2021-12-28T22:14:17.930Z</td>
      <td>1.0.0</td>
      <td>Agent</td>
      <td>Guest.185.191.171.41</td>
      <td>mailto:guest.185.191.171.41@example.com</td>
      <td>http://id.tincanapi.com/verb/viewed</td>
      <td>viewed</td>
      <td>Unknown</td>
      <td>...</td>
      <td>Mozilla/5.0 (compatible; SemrushBot/7~bl; +htt...</td>
      <td></td>
      <td>Activity</td>
      <td>https://example.com/courses/?lecture...</td>
      <td>http://activitystrea.ms/schema/1.0/page</td>
      <td>Διαχείριση Δικτύων – Ευφυή Δίκτυα, 9ο Εξάμηνο,...</td>
      <td>Viewed Page</td>
      <td>Agent</td>
      <td>NTUA NetMODe</td>
      <td>mailto:info@example.com</td>
    </tr>
    <tr>
      <th>27</th>
      <td>2021-12-28T22:06:50+00:00</td>
      <td>b2506701-5aae-4209-a4b9-6ca7f59746a8</td>
      <td>2021-12-28T22:06:50.038Z</td>
      <td>1.0.0</td>
      <td>Agent</td>
      <td>Guest.77.88.5.44</td>
      <td>mailto:guest.77.88.5.44@example.com</td>
      <td>http://id.tincanapi.com/verb/viewed</td>
      <td>viewed</td>
      <td>Unknown</td>
      <td>...</td>
      <td>Mozilla/5.0 (compatible; YandexBot/3.0; +http:...</td>
      <td></td>
      <td>Activity</td>
      <td>https://example.com/courses/?lecture...</td>
      <td>http://activitystrea.ms/schema/1.0/page</td>
      <td>Διαχείριση Δικτύων &amp;#8211; Ευφυή Δίκτυα, 9ο Εξ...</td>
      <td>Viewed Page</td>
      <td>Agent</td>
      <td>NTUA NetMODe</td>
      <td>mailto:info@example.com</td>
    </tr>
    <tr>
      <th>28</th>
      <td>2021-12-28T22:02:56+00:00</td>
      <td>b6bc1fdf-39bb-4544-bb83-47b3219f9492</td>
      <td>2021-12-28T22:02:56.558Z</td>
      <td>1.0.0</td>
      <td>Agent</td>
      <td>Guest.95.216.235.214</td>
      <td>mailto:guest.95.216.235.214@example.com</td>
      <td>http://id.tincanapi.com/verb/viewed</td>
      <td>viewed</td>
      <td>Unknown</td>
      <td>...</td>
      <td>Mozilla/5.0 (Windows NT 6.1) AppleWebKit/537.3...</td>
      <td></td>
      <td>Activity</td>
      <td>https://example.com/wp-login.php</td>
      <td>http://activitystrea.ms/schema/1.0/page</td>
      <td></td>
      <td>Viewed Page</td>
      <td>Agent</td>
      <td>NTUA NetMODe</td>
      <td>mailto:info@example.com</td>
    </tr>
    <tr>
      <th>29</th>
      <td>2021-12-28T21:47:49+00:00</td>
      <td>67c33fc7-315e-4dbd-9a02-d87640ace839</td>
      <td>2021-12-28T21:47:49.873Z</td>
      <td>1.0.0</td>
      <td>Agent</td>
      <td>Guest.40.122.130.155</td>
      <td>mailto:guest.40.122.130.155@example.com</td>
      <td>http://id.tincanapi.com/verb/viewed</td>
      <td>viewed</td>
      <td>Unknown</td>
      <td>...</td>
      <td>Mozilla/5.0 (Windows NT 6.1) AppleWebKit/537.3...</td>
      <td></td>
      <td>Activity</td>
      <td>https://example.com/wp-login.php</td>
      <td>http://activitystrea.ms/schema/1.0/page</td>
      <td></td>
      <td>Viewed Page</td>
      <td>Agent</td>
      <td>NTUA NetMODe</td>
      <td>mailto:info@example.com</td>
    </tr>
  </tbody>
</table>
<p>30 rows × 21 columns</p>
</div>




```python
# Remove any % characters, change string values to numeric values
#data[["Return on Assets"]] = data[["Return on Assets"]].apply(lambda x: x.str.replace('[%]','', regex=True))
#data[["Return on Assets",
#      "Rev per share"]] = data[["Return on Assets",
#                                   "Rev per share"]].apply(pd.to_numeric)
#data[["Return on Assets"]] = data[["Return on Assets"]].apply(lambda x: x/100)
#data.index.name = 'ID'

#data
```

### <I>2. Analyze the data, clean it and visualize it.</I>


```python
if debug > 0 :
    print ("\nData Shape")
    data.shape

if debug > 0 :
    print ("\nData Description")
    data.describe()
```


    Data Shape





    (30, 21)




    Data Description





<div>
<style scoped>
    .dataframe tbody tr th:only-of-type {
        vertical-align: middle;
    }

    .dataframe tbody tr th {
        vertical-align: top;
    }

    .dataframe thead th {
        text-align: right;
    }
</style>
<table border="1" class="dataframe">
  <thead>
    <tr style="text-align: right;">
      <th></th>
      <th>timestamp</th>
      <th>id</th>
      <th>stored</th>
      <th>version</th>
      <th>actor.objectType</th>
      <th>actor.name</th>
      <th>actor.mbox</th>
      <th>verb.id</th>
      <th>verb.display.en-US</th>
      <th>context.platform</th>
      <th>...</th>
      <th>context.extensions.http://id&amp;46;tincanapi&amp;46;com/extension/browser-info.user_agent</th>
      <th>context.extensions.http://id&amp;46;tincanapi&amp;46;com/extension/referrer</th>
      <th>object.objectType</th>
      <th>object.id</th>
      <th>object.definition.type</th>
      <th>object.definition.name.en-US</th>
      <th>object.definition.description.en-US</th>
      <th>authority.objectType</th>
      <th>authority.name</th>
      <th>authority.mbox</th>
    </tr>
  </thead>
  <tbody>
    <tr>
      <th>count</th>
      <td>30</td>
      <td>30</td>
      <td>30</td>
      <td>30</td>
      <td>30</td>
      <td>30</td>
      <td>30</td>
      <td>30</td>
      <td>30</td>
      <td>30</td>
      <td>...</td>
      <td>30</td>
      <td>30</td>
      <td>30</td>
      <td>30</td>
      <td>30</td>
      <td>30</td>
      <td>30</td>
      <td>30</td>
      <td>30</td>
      <td>30</td>
    </tr>
    <tr>
      <th>unique</th>
      <td>30</td>
      <td>30</td>
      <td>30</td>
      <td>1</td>
      <td>1</td>
      <td>28</td>
      <td>28</td>
      <td>1</td>
      <td>1</td>
      <td>1</td>
      <td>...</td>
      <td>24</td>
      <td>2</td>
      <td>1</td>
      <td>10</td>
      <td>1</td>
      <td>8</td>
      <td>1</td>
      <td>1</td>
      <td>1</td>
      <td>1</td>
    </tr>
    <tr>
      <th>top</th>
      <td>2021-12-29T04:39:20+00:00</td>
      <td>e7048d33-82cc-4d60-b29f-14d604203d53</td>
      <td>2021-12-29T04:39:25.224Z</td>
      <td>1.0.0</td>
      <td>Agent</td>
      <td>Guest.109.168.97.89</td>
      <td>mailto:guest.109.168.97.89@example.com</td>
      <td>http://id.tincanapi.com/verb/viewed</td>
      <td>viewed</td>
      <td>Unknown</td>
      <td>...</td>
      <td>Mozilla/5.0 (compatible; SemrushBot/7~bl; +htt...</td>
      <td></td>
      <td>Activity</td>
      <td>https://example.com/wp-login.php</td>
      <td>http://activitystrea.ms/schema/1.0/page</td>
      <td></td>
      <td>Viewed Page</td>
      <td>Agent</td>
      <td>NTUA NetMODe</td>
      <td>mailto:info@example.com</td>
    </tr>
    <tr>
      <th>freq</th>
      <td>1</td>
      <td>1</td>
      <td>1</td>
      <td>30</td>
      <td>30</td>
      <td>2</td>
      <td>2</td>
      <td>30</td>
      <td>30</td>
      <td>30</td>
      <td>...</td>
      <td>3</td>
      <td>29</td>
      <td>30</td>
      <td>21</td>
      <td>30</td>
      <td>22</td>
      <td>30</td>
      <td>30</td>
      <td>30</td>
      <td>30</td>
    </tr>
  </tbody>
</table>
<p>4 rows × 21 columns</p>
</div>




```python
# Make a copy of the original data before starting our data preprocessing
original_data=data.copy()

#Check NA Values
data[data['verb.display.en-US'].isna() | data['actor.name'].isna()]
```




<div>
<style scoped>
    .dataframe tbody tr th:only-of-type {
        vertical-align: middle;
    }

    .dataframe tbody tr th {
        vertical-align: top;
    }

    .dataframe thead th {
        text-align: right;
    }
</style>
<table border="1" class="dataframe">
  <thead>
    <tr style="text-align: right;">
      <th></th>
      <th>timestamp</th>
      <th>id</th>
      <th>stored</th>
      <th>version</th>
      <th>actor.objectType</th>
      <th>actor.name</th>
      <th>actor.mbox</th>
      <th>verb.id</th>
      <th>verb.display.en-US</th>
      <th>context.platform</th>
      <th>...</th>
      <th>context.extensions.http://id&amp;46;tincanapi&amp;46;com/extension/browser-info.user_agent</th>
      <th>context.extensions.http://id&amp;46;tincanapi&amp;46;com/extension/referrer</th>
      <th>object.objectType</th>
      <th>object.id</th>
      <th>object.definition.type</th>
      <th>object.definition.name.en-US</th>
      <th>object.definition.description.en-US</th>
      <th>authority.objectType</th>
      <th>authority.name</th>
      <th>authority.mbox</th>
    </tr>
  </thead>
  <tbody>
  </tbody>
</table>
<p>0 rows × 21 columns</p>
</div>




```python
# Dropna value
data=data.dropna()
data
```




<div>
<style scoped>
    .dataframe tbody tr th:only-of-type {
        vertical-align: middle;
    }

    .dataframe tbody tr th {
        vertical-align: top;
    }

    .dataframe thead th {
        text-align: right;
    }
</style>
<table border="1" class="dataframe">
  <thead>
    <tr style="text-align: right;">
      <th></th>
      <th>timestamp</th>
      <th>id</th>
      <th>stored</th>
      <th>version</th>
      <th>actor.objectType</th>
      <th>actor.name</th>
      <th>actor.mbox</th>
      <th>verb.id</th>
      <th>verb.display.en-US</th>
      <th>context.platform</th>
      <th>...</th>
      <th>context.extensions.http://id&amp;46;tincanapi&amp;46;com/extension/browser-info.user_agent</th>
      <th>context.extensions.http://id&amp;46;tincanapi&amp;46;com/extension/referrer</th>
      <th>object.objectType</th>
      <th>object.id</th>
      <th>object.definition.type</th>
      <th>object.definition.name.en-US</th>
      <th>object.definition.description.en-US</th>
      <th>authority.objectType</th>
      <th>authority.name</th>
      <th>authority.mbox</th>
    </tr>
  </thead>
  <tbody>
    <tr>
      <th>0</th>
      <td>2021-12-29T04:39:20+00:00</td>
      <td>e7048d33-82cc-4d60-b29f-14d604203d53</td>
      <td>2021-12-29T04:39:25.224Z</td>
      <td>1.0.0</td>
      <td>Agent</td>
      <td>Guest.115.29.199.218</td>
      <td>mailto:guest.115.29.199.218@example.com</td>
      <td>http://id.tincanapi.com/verb/viewed</td>
      <td>viewed</td>
      <td>Unknown</td>
      <td>...</td>
      <td>Mozilla/5.0 (Windows NT 6.1; Win64; x64) Apple...</td>
      <td></td>
      <td>Activity</td>
      <td>https://example.com/wp-login.php</td>
      <td>http://activitystrea.ms/schema/1.0/page</td>
      <td></td>
      <td>Viewed Page</td>
      <td>Agent</td>
      <td>NTUA NetMODe</td>
      <td>mailto:info@example.com</td>
    </tr>
    <tr>
      <th>1</th>
      <td>2021-12-29T04:23:58+00:00</td>
      <td>1de0f462-f2d5-4822-a246-c07a925eb447</td>
      <td>2021-12-29T04:23:58.246Z</td>
      <td>1.0.0</td>
      <td>Agent</td>
      <td>Guest.212.103.4.29</td>
      <td>mailto:guest.212.103.4.29@example.com</td>
      <td>http://id.tincanapi.com/verb/viewed</td>
      <td>viewed</td>
      <td>Unknown</td>
      <td>...</td>
      <td>Mozilla/5.0 (Windows NT 10.0; Win64; x64) Appl...</td>
      <td></td>
      <td>Activity</td>
      <td>https://example.com/wp-login.php</td>
      <td>http://activitystrea.ms/schema/1.0/page</td>
      <td></td>
      <td>Viewed Page</td>
      <td>Agent</td>
      <td>NTUA NetMODe</td>
      <td>mailto:info@example.com</td>
    </tr>
    <tr>
      <th>2</th>
      <td>2021-12-29T04:08:55+00:00</td>
      <td>6a984cb0-92ff-48a1-a632-b77f53494390</td>
      <td>2021-12-29T04:08:55.557Z</td>
      <td>1.0.0</td>
      <td>Agent</td>
      <td>Guest.188.166.152.29</td>
      <td>mailto:guest.188.166.152.29@example.com</td>
      <td>http://id.tincanapi.com/verb/viewed</td>
      <td>viewed</td>
      <td>Unknown</td>
      <td>...</td>
      <td>Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKi...</td>
      <td></td>
      <td>Activity</td>
      <td>https://example.com/wp-login.php</td>
      <td>http://activitystrea.ms/schema/1.0/page</td>
      <td></td>
      <td>Viewed Page</td>
      <td>Agent</td>
      <td>NTUA NetMODe</td>
      <td>mailto:info@example.com</td>
    </tr>
    <tr>
      <th>3</th>
      <td>2021-12-29T03:53:54+00:00</td>
      <td>bc89786b-5544-4597-a1ef-7ee665a53e2f</td>
      <td>2021-12-29T03:53:54.498Z</td>
      <td>1.0.0</td>
      <td>Agent</td>
      <td>Guest.35.241.177.128</td>
      <td>mailto:guest.35.241.177.128@example.com</td>
      <td>http://id.tincanapi.com/verb/viewed</td>
      <td>viewed</td>
      <td>Unknown</td>
      <td>...</td>
      <td>Mozilla/5.0 (Windows NT 6.1; Win64; x64) Apple...</td>
      <td></td>
      <td>Activity</td>
      <td>https://example.com/wp-login.php</td>
      <td>http://activitystrea.ms/schema/1.0/page</td>
      <td></td>
      <td>Viewed Page</td>
      <td>Agent</td>
      <td>NTUA NetMODe</td>
      <td>mailto:info@example.com</td>
    </tr>
    <tr>
      <th>4</th>
      <td>2021-12-29T03:38:45+00:00</td>
      <td>d3bd2bfd-3350-4b23-9d22-37d447a08a47</td>
      <td>2021-12-29T03:38:45.230Z</td>
      <td>1.0.0</td>
      <td>Agent</td>
      <td>Guest.109.168.97.89</td>
      <td>mailto:guest.109.168.97.89@example.com</td>
      <td>http://id.tincanapi.com/verb/viewed</td>
      <td>viewed</td>
      <td>Unknown</td>
      <td>...</td>
      <td>Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKi...</td>
      <td></td>
      <td>Activity</td>
      <td>https://example.com/wp-login.php</td>
      <td>http://activitystrea.ms/schema/1.0/page</td>
      <td></td>
      <td>Viewed Page</td>
      <td>Agent</td>
      <td>NTUA NetMODe</td>
      <td>mailto:info@example.com</td>
    </tr>
    <tr>
      <th>5</th>
      <td>2021-12-29T03:23:35+00:00</td>
      <td>48033410-76a9-4e4c-b098-9c867d5de538</td>
      <td>2021-12-29T03:23:35.118Z</td>
      <td>1.0.0</td>
      <td>Agent</td>
      <td>Guest.65.21.234.156</td>
      <td>mailto:guest.65.21.234.156@example.com</td>
      <td>http://id.tincanapi.com/verb/viewed</td>
      <td>viewed</td>
      <td>Unknown</td>
      <td>...</td>
      <td>Mozilla/5.0 (Windows NT 6.1) AppleWebKit/537.3...</td>
      <td></td>
      <td>Activity</td>
      <td>https://example.com/wp-login.php</td>
      <td>http://activitystrea.ms/schema/1.0/page</td>
      <td></td>
      <td>Viewed Page</td>
      <td>Agent</td>
      <td>NTUA NetMODe</td>
      <td>mailto:info@example.com</td>
    </tr>
    <tr>
      <th>6</th>
      <td>2021-12-29T02:52:57+00:00</td>
      <td>27c2cd27-b061-469c-ad77-1e89a1585056</td>
      <td>2021-12-29T02:52:57.445Z</td>
      <td>1.0.0</td>
      <td>Agent</td>
      <td>Guest.109.168.97.89</td>
      <td>mailto:guest.109.168.97.89@example.com</td>
      <td>http://id.tincanapi.com/verb/viewed</td>
      <td>viewed</td>
      <td>Unknown</td>
      <td>...</td>
      <td>Mozilla/5.0 (Windows NT 6.3; Win64; x64) Apple...</td>
      <td></td>
      <td>Activity</td>
      <td>https://example.com/wp-login.php</td>
      <td>http://activitystrea.ms/schema/1.0/page</td>
      <td></td>
      <td>Viewed Page</td>
      <td>Agent</td>
      <td>NTUA NetMODe</td>
      <td>mailto:info@example.com</td>
    </tr>
    <tr>
      <th>7</th>
      <td>2021-12-29T02:50:17+00:00</td>
      <td>d684ce9e-4bbf-478d-bc8f-bc8a70e630e5</td>
      <td>2021-12-29T02:50:17.697Z</td>
      <td>1.0.0</td>
      <td>Agent</td>
      <td>Guest.207.46.13.197</td>
      <td>mailto:guest.207.46.13.197@example.com</td>
      <td>http://id.tincanapi.com/verb/viewed</td>
      <td>viewed</td>
      <td>Unknown</td>
      <td>...</td>
      <td>Mozilla/5.0 (compatible; bingbot/2.0; +http://...</td>
      <td></td>
      <td>Activity</td>
      <td>https://example.com/courses/?lecture...</td>
      <td>http://activitystrea.ms/schema/1.0/page</td>
      <td>Διαχείριση Δικτύων – Ευφυή Δίκτυα, 9ο Εξάμηνο,...</td>
      <td>Viewed Page</td>
      <td>Agent</td>
      <td>NTUA NetMODe</td>
      <td>mailto:info@example.com</td>
    </tr>
    <tr>
      <th>8</th>
      <td>2021-12-29T02:22:20+00:00</td>
      <td>404fb2f5-ba7d-4463-a699-0de42d7037bb</td>
      <td>2021-12-29T02:22:20.463Z</td>
      <td>1.0.0</td>
      <td>Agent</td>
      <td>Guest.107.172.82.148</td>
      <td>mailto:guest.107.172.82.148@example.com</td>
      <td>http://id.tincanapi.com/verb/viewed</td>
      <td>viewed</td>
      <td>Unknown</td>
      <td>...</td>
      <td>Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US...</td>
      <td></td>
      <td>Activity</td>
      <td>https://example.com/wp-login.php</td>
      <td>http://activitystrea.ms/schema/1.0/page</td>
      <td></td>
      <td>Viewed Page</td>
      <td>Agent</td>
      <td>NTUA NetMODe</td>
      <td>mailto:info@example.com</td>
    </tr>
    <tr>
      <th>9</th>
      <td>2021-12-29T02:06:55+00:00</td>
      <td>b2cf9f12-214b-45a9-86fb-ad24053b54c2</td>
      <td>2021-12-29T02:06:55.439Z</td>
      <td>1.0.0</td>
      <td>Agent</td>
      <td>Guest.173.212.235.115</td>
      <td>mailto:guest.173.212.235.115@example.com</td>
      <td>http://id.tincanapi.com/verb/viewed</td>
      <td>viewed</td>
      <td>Unknown</td>
      <td>...</td>
      <td>Mozilla/5.0 (Windows NT 10.0; Win64; x64) Appl...</td>
      <td></td>
      <td>Activity</td>
      <td>https://example.com/wp-login.php</td>
      <td>http://activitystrea.ms/schema/1.0/page</td>
      <td></td>
      <td>Viewed Page</td>
      <td>Agent</td>
      <td>NTUA NetMODe</td>
      <td>mailto:info@example.com</td>
    </tr>
    <tr>
      <th>10</th>
      <td>2021-12-29T01:51:38+00:00</td>
      <td>25e6f0a0-2d22-48f6-9776-4c35d664b821</td>
      <td>2021-12-29T01:51:43.095Z</td>
      <td>1.0.0</td>
      <td>Agent</td>
      <td>Guest.157.245.217.209</td>
      <td>mailto:guest.157.245.217.209@example.com</td>
      <td>http://id.tincanapi.com/verb/viewed</td>
      <td>viewed</td>
      <td>Unknown</td>
      <td>...</td>
      <td>Mozilla/5.0 (Windows NT 6.1; Win64; x64) Apple...</td>
      <td></td>
      <td>Activity</td>
      <td>https://example.com/wp-login.php</td>
      <td>http://activitystrea.ms/schema/1.0/page</td>
      <td></td>
      <td>Viewed Page</td>
      <td>Agent</td>
      <td>NTUA NetMODe</td>
      <td>mailto:info@example.com</td>
    </tr>
    <tr>
      <th>11</th>
      <td>2021-12-29T01:36:18+00:00</td>
      <td>becefa60-19b7-49cd-91b9-66fccbc4e330</td>
      <td>2021-12-29T01:36:18.724Z</td>
      <td>1.0.0</td>
      <td>Agent</td>
      <td>Guest.161.35.94.99</td>
      <td>mailto:guest.161.35.94.99@example.com</td>
      <td>http://id.tincanapi.com/verb/viewed</td>
      <td>viewed</td>
      <td>Unknown</td>
      <td>...</td>
      <td>Mozilla/5.0 (Windows NT 6.1) AppleWebKit/537.3...</td>
      <td></td>
      <td>Activity</td>
      <td>https://example.com/wp-login.php</td>
      <td>http://activitystrea.ms/schema/1.0/page</td>
      <td></td>
      <td>Viewed Page</td>
      <td>Agent</td>
      <td>NTUA NetMODe</td>
      <td>mailto:info@example.com</td>
    </tr>
    <tr>
      <th>12</th>
      <td>2021-12-29T01:21:19+00:00</td>
      <td>441a84c2-8471-47ad-8cf2-0189df8bec00</td>
      <td>2021-12-29T01:21:19.502Z</td>
      <td>1.0.0</td>
      <td>Agent</td>
      <td>Guest.34.96.130.6</td>
      <td>mailto:guest.34.96.130.6@example.com</td>
      <td>http://id.tincanapi.com/verb/viewed</td>
      <td>viewed</td>
      <td>Unknown</td>
      <td>...</td>
      <td>Expanse indexes the network perimeters of our ...</td>
      <td></td>
      <td>Activity</td>
      <td>https://example.com/</td>
      <td>http://activitystrea.ms/schema/1.0/page</td>
      <td>Διαλέξεις Μαθημάτων NETMODE</td>
      <td>Viewed Page</td>
      <td>Agent</td>
      <td>NTUA NetMODe</td>
      <td>mailto:info@example.com</td>
    </tr>
    <tr>
      <th>13</th>
      <td>2021-12-29T01:05:47+00:00</td>
      <td>34b69e43-d137-4d85-9fb7-6dc9723ee9c9</td>
      <td>2021-12-29T01:05:47.067Z</td>
      <td>1.0.0</td>
      <td>Agent</td>
      <td>Guest.188.166.236.240</td>
      <td>mailto:guest.188.166.236.240@example.com</td>
      <td>http://id.tincanapi.com/verb/viewed</td>
      <td>viewed</td>
      <td>Unknown</td>
      <td>...</td>
      <td>Mozilla/5.0 (Windows NT 10.0; WOW64) AppleWebK...</td>
      <td></td>
      <td>Activity</td>
      <td>https://example.com/wp-login.php</td>
      <td>http://activitystrea.ms/schema/1.0/page</td>
      <td></td>
      <td>Viewed Page</td>
      <td>Agent</td>
      <td>NTUA NetMODe</td>
      <td>mailto:info@example.com</td>
    </tr>
    <tr>
      <th>14</th>
      <td>2021-12-29T00:50:29+00:00</td>
      <td>0f90c9c5-1bf6-4117-90bf-585a3ea96f2f</td>
      <td>2021-12-29T00:50:29.976Z</td>
      <td>1.0.0</td>
      <td>Agent</td>
      <td>Guest.128.199.242.105</td>
      <td>mailto:guest.128.199.242.105@example.com</td>
      <td>http://id.tincanapi.com/verb/viewed</td>
      <td>viewed</td>
      <td>Unknown</td>
      <td>...</td>
      <td>Mozilla/5.0 (Windows NT 6.1; Win64; x64) Apple...</td>
      <td></td>
      <td>Activity</td>
      <td>https://example.com/wp-login.php</td>
      <td>http://activitystrea.ms/schema/1.0/page</td>
      <td></td>
      <td>Viewed Page</td>
      <td>Agent</td>
      <td>NTUA NetMODe</td>
      <td>mailto:info@example.com</td>
    </tr>
    <tr>
      <th>15</th>
      <td>2021-12-29T00:34:38+00:00</td>
      <td>6f0d3e96-560b-4b84-9e0e-283ce5333a49</td>
      <td>2021-12-29T00:34:38.655Z</td>
      <td>1.0.0</td>
      <td>Agent</td>
      <td>Guest.77.88.5.245</td>
      <td>mailto:guest.77.88.5.245@example.com</td>
      <td>http://id.tincanapi.com/verb/viewed</td>
      <td>viewed</td>
      <td>Unknown</td>
      <td>...</td>
      <td>Mozilla/5.0 (compatible; YandexBot/3.0; +http:...</td>
      <td></td>
      <td>Activity</td>
      <td>https://example.com/courses/?lecture...</td>
      <td>http://activitystrea.ms/schema/1.0/page</td>
      <td>Network Management &amp;#8211; Intelligent Network...</td>
      <td>Viewed Page</td>
      <td>Agent</td>
      <td>NTUA NetMODe</td>
      <td>mailto:info@example.com</td>
    </tr>
    <tr>
      <th>16</th>
      <td>2021-12-29T00:20:01+00:00</td>
      <td>ac9c2973-05cc-4cf7-b9f6-8d8bb286aee8</td>
      <td>2021-12-29T00:20:01.359Z</td>
      <td>1.0.0</td>
      <td>Agent</td>
      <td>Guest.133.130.102.247</td>
      <td>mailto:guest.133.130.102.247@example.com</td>
      <td>http://id.tincanapi.com/verb/viewed</td>
      <td>viewed</td>
      <td>Unknown</td>
      <td>...</td>
      <td>Mozilla/5.0 (Windows NT 10.0; Win64; x64) Appl...</td>
      <td></td>
      <td>Activity</td>
      <td>https://example.com/wp-login.php</td>
      <td>http://activitystrea.ms/schema/1.0/page</td>
      <td></td>
      <td>Viewed Page</td>
      <td>Agent</td>
      <td>NTUA NetMODe</td>
      <td>mailto:info@example.com</td>
    </tr>
    <tr>
      <th>17</th>
      <td>2021-12-29T00:07:15+00:00</td>
      <td>4c948a67-63ff-4c03-b72f-2b1a950e58db</td>
      <td>2021-12-29T00:07:15.472Z</td>
      <td>1.0.0</td>
      <td>Agent</td>
      <td>Guest.69.30.240.28</td>
      <td>mailto:guest.69.30.240.28@example.com</td>
      <td>http://id.tincanapi.com/verb/viewed</td>
      <td>viewed</td>
      <td>Unknown</td>
      <td>...</td>
      <td>Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKi...</td>
      <td>http://www.google.com.hk</td>
      <td>Activity</td>
      <td>https://example.com//xmlrpc.php</td>
      <td>http://activitystrea.ms/schema/1.0/page</td>
      <td></td>
      <td>Viewed Page</td>
      <td>Agent</td>
      <td>NTUA NetMODe</td>
      <td>mailto:info@example.com</td>
    </tr>
    <tr>
      <th>18</th>
      <td>2021-12-29T00:04:42+00:00</td>
      <td>c969f849-3f1b-4099-829c-ab1ee7790aca</td>
      <td>2021-12-29T00:04:42.728Z</td>
      <td>1.0.0</td>
      <td>Agent</td>
      <td>Guest.173.255.112.220</td>
      <td>mailto:guest.173.255.112.220@example.com</td>
      <td>http://id.tincanapi.com/verb/viewed</td>
      <td>viewed</td>
      <td>Unknown</td>
      <td>...</td>
      <td>Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKi...</td>
      <td></td>
      <td>Activity</td>
      <td>https://example.com/wp-login.php</td>
      <td>http://activitystrea.ms/schema/1.0/page</td>
      <td></td>
      <td>Viewed Page</td>
      <td>Agent</td>
      <td>NTUA NetMODe</td>
      <td>mailto:info@example.com</td>
    </tr>
    <tr>
      <th>19</th>
      <td>2021-12-28T23:34:13+00:00</td>
      <td>ee513cdb-f6ae-47f7-bca1-3e0e5ba64a00</td>
      <td>2021-12-28T23:34:13.802Z</td>
      <td>1.0.0</td>
      <td>Agent</td>
      <td>Guest.95.217.3.203</td>
      <td>mailto:guest.95.217.3.203@example.com</td>
      <td>http://id.tincanapi.com/verb/viewed</td>
      <td>viewed</td>
      <td>Unknown</td>
      <td>...</td>
      <td>Mozilla/5.0 (Windows NT 6.1) AppleWebKit/537.3...</td>
      <td></td>
      <td>Activity</td>
      <td>https://example.com/wp-login.php</td>
      <td>http://activitystrea.ms/schema/1.0/page</td>
      <td></td>
      <td>Viewed Page</td>
      <td>Agent</td>
      <td>NTUA NetMODe</td>
      <td>mailto:info@example.com</td>
    </tr>
    <tr>
      <th>20</th>
      <td>2021-12-28T23:23:54+00:00</td>
      <td>94ed85d9-ead3-450d-aeb4-8f4a8f6003f5</td>
      <td>2021-12-28T23:23:54.763Z</td>
      <td>1.0.0</td>
      <td>Agent</td>
      <td>Guest.185.191.171.39</td>
      <td>mailto:guest.185.191.171.39@example.com</td>
      <td>http://id.tincanapi.com/verb/viewed</td>
      <td>viewed</td>
      <td>Unknown</td>
      <td>...</td>
      <td>Mozilla/5.0 (compatible; SemrushBot/7~bl; +htt...</td>
      <td></td>
      <td>Activity</td>
      <td>https://example.com/courses/?lecture...</td>
      <td>http://activitystrea.ms/schema/1.0/page</td>
      <td>Διαχείριση Δικτύων – Ευφυή Δίκτυα, 9ο Εξάμηνο,...</td>
      <td>Viewed Page</td>
      <td>Agent</td>
      <td>NTUA NetMODe</td>
      <td>mailto:info@example.com</td>
    </tr>
    <tr>
      <th>21</th>
      <td>2021-12-28T23:17:35+00:00</td>
      <td>7176c726-ca24-4734-88f9-7362e7ef9567</td>
      <td>2021-12-28T23:17:35.899Z</td>
      <td>1.0.0</td>
      <td>Agent</td>
      <td>Guest.77.88.5.44</td>
      <td>mailto:guest.77.88.5.44@example.com</td>
      <td>http://id.tincanapi.com/verb/viewed</td>
      <td>viewed</td>
      <td>Unknown</td>
      <td>...</td>
      <td>Mozilla/5.0 (compatible; YandexBot/3.0; +http:...</td>
      <td></td>
      <td>Activity</td>
      <td>https://example.com/courses/</td>
      <td>http://activitystrea.ms/schema/1.0/page</td>
      <td>Διαλέξεις Μαθημάτων NETMODE</td>
      <td>Viewed Page</td>
      <td>Agent</td>
      <td>NTUA NetMODe</td>
      <td>mailto:info@example.com</td>
    </tr>
    <tr>
      <th>22</th>
      <td>2021-12-28T23:03:48+00:00</td>
      <td>6cb279fc-359d-4b8d-8cb2-8bcef73b66ff</td>
      <td>2021-12-28T23:03:48.472Z</td>
      <td>1.0.0</td>
      <td>Agent</td>
      <td>Guest.46.101.150.34</td>
      <td>mailto:guest.46.101.150.34@example.com</td>
      <td>http://id.tincanapi.com/verb/viewed</td>
      <td>viewed</td>
      <td>Unknown</td>
      <td>...</td>
      <td>Mozilla/5.0 (Windows NT 6.1; Win64; x64) Apple...</td>
      <td></td>
      <td>Activity</td>
      <td>https://example.com/wp-login.php</td>
      <td>http://activitystrea.ms/schema/1.0/page</td>
      <td></td>
      <td>Viewed Page</td>
      <td>Agent</td>
      <td>NTUA NetMODe</td>
      <td>mailto:info@example.com</td>
    </tr>
    <tr>
      <th>23</th>
      <td>2021-12-28T22:48:33+00:00</td>
      <td>6c1dcdf3-6350-4555-8777-8e71995914ac</td>
      <td>2021-12-28T22:48:33.194Z</td>
      <td>1.0.0</td>
      <td>Agent</td>
      <td>Guest.159.65.76.38</td>
      <td>mailto:guest.159.65.76.38@example.com</td>
      <td>http://id.tincanapi.com/verb/viewed</td>
      <td>viewed</td>
      <td>Unknown</td>
      <td>...</td>
      <td>Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKi...</td>
      <td></td>
      <td>Activity</td>
      <td>https://example.com/wp-login.php</td>
      <td>http://activitystrea.ms/schema/1.0/page</td>
      <td></td>
      <td>Viewed Page</td>
      <td>Agent</td>
      <td>NTUA NetMODe</td>
      <td>mailto:info@example.com</td>
    </tr>
    <tr>
      <th>24</th>
      <td>2021-12-28T22:33:21+00:00</td>
      <td>fa16547d-ecd8-417d-a2e9-89916791b821</td>
      <td>2021-12-28T22:33:21.335Z</td>
      <td>1.0.0</td>
      <td>Agent</td>
      <td>Guest.207.180.213.165</td>
      <td>mailto:guest.207.180.213.165@example.com</td>
      <td>http://id.tincanapi.com/verb/viewed</td>
      <td>viewed</td>
      <td>Unknown</td>
      <td>...</td>
      <td>Mozilla/5.0 (Windows NT 6.1; Win64; x64) Apple...</td>
      <td></td>
      <td>Activity</td>
      <td>https://example.com/wp-login.php</td>
      <td>http://activitystrea.ms/schema/1.0/page</td>
      <td></td>
      <td>Viewed Page</td>
      <td>Agent</td>
      <td>NTUA NetMODe</td>
      <td>mailto:info@example.com</td>
    </tr>
    <tr>
      <th>25</th>
      <td>2021-12-28T22:26:35+00:00</td>
      <td>d3b01d9a-87b6-436b-af1f-cf5efe282651</td>
      <td>2021-12-28T22:26:35.336Z</td>
      <td>1.0.0</td>
      <td>Agent</td>
      <td>Guest.185.191.171.19</td>
      <td>mailto:guest.185.191.171.19@example.com</td>
      <td>http://id.tincanapi.com/verb/viewed</td>
      <td>viewed</td>
      <td>Unknown</td>
      <td>...</td>
      <td>Mozilla/5.0 (compatible; SemrushBot/7~bl; +htt...</td>
      <td></td>
      <td>Activity</td>
      <td>https://example.com/courses/?lecture...</td>
      <td>http://activitystrea.ms/schema/1.0/page</td>
      <td>Συστήματα Αναμονής, 6ο Εξάμηνο: Εισαγωγή (Μέρο...</td>
      <td>Viewed Page</td>
      <td>Agent</td>
      <td>NTUA NetMODe</td>
      <td>mailto:info@example.com</td>
    </tr>
    <tr>
      <th>26</th>
      <td>2021-12-28T22:14:12+00:00</td>
      <td>13cf6340-6df2-4c33-ae87-6abe820fe3e4</td>
      <td>2021-12-28T22:14:17.930Z</td>
      <td>1.0.0</td>
      <td>Agent</td>
      <td>Guest.185.191.171.41</td>
      <td>mailto:guest.185.191.171.41@example.com</td>
      <td>http://id.tincanapi.com/verb/viewed</td>
      <td>viewed</td>
      <td>Unknown</td>
      <td>...</td>
      <td>Mozilla/5.0 (compatible; SemrushBot/7~bl; +htt...</td>
      <td></td>
      <td>Activity</td>
      <td>https://example.com/courses/?lecture...</td>
      <td>http://activitystrea.ms/schema/1.0/page</td>
      <td>Διαχείριση Δικτύων – Ευφυή Δίκτυα, 9ο Εξάμηνο,...</td>
      <td>Viewed Page</td>
      <td>Agent</td>
      <td>NTUA NetMODe</td>
      <td>mailto:info@example.com</td>
    </tr>
    <tr>
      <th>27</th>
      <td>2021-12-28T22:06:50+00:00</td>
      <td>b2506701-5aae-4209-a4b9-6ca7f59746a8</td>
      <td>2021-12-28T22:06:50.038Z</td>
      <td>1.0.0</td>
      <td>Agent</td>
      <td>Guest.77.88.5.44</td>
      <td>mailto:guest.77.88.5.44@example.com</td>
      <td>http://id.tincanapi.com/verb/viewed</td>
      <td>viewed</td>
      <td>Unknown</td>
      <td>...</td>
      <td>Mozilla/5.0 (compatible; YandexBot/3.0; +http:...</td>
      <td></td>
      <td>Activity</td>
      <td>https://example.com/courses/?lecture...</td>
      <td>http://activitystrea.ms/schema/1.0/page</td>
      <td>Διαχείριση Δικτύων &amp;#8211; Ευφυή Δίκτυα, 9ο Εξ...</td>
      <td>Viewed Page</td>
      <td>Agent</td>
      <td>NTUA NetMODe</td>
      <td>mailto:info@example.com</td>
    </tr>
    <tr>
      <th>28</th>
      <td>2021-12-28T22:02:56+00:00</td>
      <td>b6bc1fdf-39bb-4544-bb83-47b3219f9492</td>
      <td>2021-12-28T22:02:56.558Z</td>
      <td>1.0.0</td>
      <td>Agent</td>
      <td>Guest.95.216.235.214</td>
      <td>mailto:guest.95.216.235.214@example.com</td>
      <td>http://id.tincanapi.com/verb/viewed</td>
      <td>viewed</td>
      <td>Unknown</td>
      <td>...</td>
      <td>Mozilla/5.0 (Windows NT 6.1) AppleWebKit/537.3...</td>
      <td></td>
      <td>Activity</td>
      <td>https://example.com/wp-login.php</td>
      <td>http://activitystrea.ms/schema/1.0/page</td>
      <td></td>
      <td>Viewed Page</td>
      <td>Agent</td>
      <td>NTUA NetMODe</td>
      <td>mailto:info@example.com</td>
    </tr>
    <tr>
      <th>29</th>
      <td>2021-12-28T21:47:49+00:00</td>
      <td>67c33fc7-315e-4dbd-9a02-d87640ace839</td>
      <td>2021-12-28T21:47:49.873Z</td>
      <td>1.0.0</td>
      <td>Agent</td>
      <td>Guest.40.122.130.155</td>
      <td>mailto:guest.40.122.130.155@example.com</td>
      <td>http://id.tincanapi.com/verb/viewed</td>
      <td>viewed</td>
      <td>Unknown</td>
      <td>...</td>
      <td>Mozilla/5.0 (Windows NT 6.1) AppleWebKit/537.3...</td>
      <td></td>
      <td>Activity</td>
      <td>https://example.com/wp-login.php</td>
      <td>http://activitystrea.ms/schema/1.0/page</td>
      <td></td>
      <td>Viewed Page</td>
      <td>Agent</td>
      <td>NTUA NetMODe</td>
      <td>mailto:info@example.com</td>
    </tr>
  </tbody>
</table>
<p>30 rows × 21 columns</p>
</div>




```python
# Visualize scatterplot
plt.style.use("dark_background")
g = sns.scatterplot(x='object.id', y='object.definition.description.en-US', data=data)
plt.ylim([0,200])
plt.title("Original Data")

# Some random point we want to classify
plt.scatter(0.05, 50, marker='o', s=80, color='red')
```




    (0.0, 200.0)






    Text(0.5, 1.0, 'Original Data')






    <matplotlib.collections.PathCollection at 0x7f2bc84dc250>





![png](output_files/output_29_3.png)




```python
# Both Revenue per share and Return on Assets are ratios. They are already scaled to the company size.
# We can use Winsorization to transforms data by limiting extreme values, typically by setting all outliers to a specified percentile of data
X =np.asarray([np.asarray(data['Return on Assets']),np.asarray(data['Rev per share'])])
X = mstats.winsorize(X, limits = [0.05, 0.05])
data=pd.DataFrame(X, index=['Return on Assets','Rev per share'], columns=data.index).T
data.head()
```


    ---------------------------------------------------------------------------

    KeyError                                  Traceback (most recent call last)

    File /usr/local/lib/python3.9/dist-packages/pandas/core/indexes/base.py:3621, in Index.get_loc(self, key, method, tolerance)
       3620 try:
    -> 3621     return self._engine.get_loc(casted_key)
       3622 except KeyError as err:


    File /usr/local/lib/python3.9/dist-packages/pandas/_libs/index.pyx:136, in pandas._libs.index.IndexEngine.get_loc()


    File /usr/local/lib/python3.9/dist-packages/pandas/_libs/index.pyx:163, in pandas._libs.index.IndexEngine.get_loc()


    File pandas/_libs/hashtable_class_helper.pxi:5198, in pandas._libs.hashtable.PyObjectHashTable.get_item()


    File pandas/_libs/hashtable_class_helper.pxi:5206, in pandas._libs.hashtable.PyObjectHashTable.get_item()


    KeyError: 'Return on Assets'


    The above exception was the direct cause of the following exception:


    KeyError                                  Traceback (most recent call last)

    Input In [8], in <cell line: 3>()
          1 # Both Revenue per share and Return on Assets are ratios. They are already scaled to the company size.
          2 # We can use Winsorization to transforms data by limiting extreme values, typically by setting all outliers to a specified percentile of data
    ----> 3 X =np.asarray([np.asarray(data['Return on Assets']),np.asarray(data['Rev per share'])])
          4 X = mstats.winsorize(X, limits = [0.05, 0.05])
          5 data=pd.DataFrame(X, index=['Return on Assets','Rev per share'], columns=data.index).T


    File /usr/local/lib/python3.9/dist-packages/pandas/core/frame.py:3505, in DataFrame.__getitem__(self, key)
       3503 if self.columns.nlevels > 1:
       3504     return self._getitem_multilevel(key)
    -> 3505 indexer = self.columns.get_loc(key)
       3506 if is_integer(indexer):
       3507     indexer = [indexer]


    File /usr/local/lib/python3.9/dist-packages/pandas/core/indexes/base.py:3623, in Index.get_loc(self, key, method, tolerance)
       3621     return self._engine.get_loc(casted_key)
       3622 except KeyError as err:
    -> 3623     raise KeyError(key) from err
       3624 except TypeError:
       3625     # If we have a listlike key, _check_indexing_error will raise
       3626     #  InvalidIndexError. Otherwise we fall through and re-raise
       3627     #  the TypeError.
       3628     self._check_indexing_error(key)


    KeyError: 'Return on Assets'



```python
# Visualize scatterplot
plt.style.use("dark_background")
g = sns.scatterplot(x='Return on Assets', y='Rev per share', data=data)
plt.title("Winsorized Data")

# Some random point we want to classify
plt.scatter(0.05, 50, marker='o', s=80, color='red')
plt.show()
```

### <I>3. Choose K</I>

The two most common methods to choose K ( the appropriate number of clusters) are :
    <ul>
        <li>The silhouette Coefficient</li>
        <li>The Elbow Method </li>
    </ul>

The silhouette coefficient is a value that ranges between -1 and 1. It quantifies how well a data point fits into its assigned cluster based on two factors:
1. How close the data point is to other points in the cluster
2. How far away the data point is from points in other clusters

Larger numbers for Silhouette coefficient indicate that samples are closer to their clusters than they are to other clusters.

The elbow method is used by running several k-means, increment k with each iteration, and record the SSE ( Sum Of Squared Error) <br><br>
$$SSE= Sum  \; Of  \; Euclidean  \; Squared  \; Distances  \; of  \; each  \; point \; to \; its  \; closest \; centroid $$<br>
After that , we plot SSE as a function of the number of clusters. SSE continues to decrease as you increase k. As more centroids are added, the distance from each point to its closest centroid will decrease.
There’s a sweet spot where the SSE curve starts to bend known as the elbow point. The x-value of this point is thought to be a reasonable trade-off between error and number of clusters. <br>

<a href="https://realpython.com/k-means-clustering-python/#choosing-the-appropriate-number-of-clusters"> (Source)</a>

In this example, we will use the Elbow Method to determine K:


```python
distorsions = []
clusters_iterations=range(2, 20)
for k in clusters_iterations:
    k_means = KMeans(n_clusters=k)
    k_means.fit(data)
    distorsions.append(k_means.inertia_)
```


```python
elbow_curve_data=pd.DataFrame(zip(clusters_iterations,distorsions),columns=['Cluster','SSE']).set_index('Cluster')
elbow_curve_data.head()
```


```python
# Visualize plot
plt.figure(figsize=(11,7))
plt.style.use("dark_background")
plt.plot(elbow_curve_data['SSE'])
plt.title("Elbow Curve")

plt.show()
```


```python
# get elbow programmatically
from kneed import KneeLocator
kl = KneeLocator(
clusters_iterations, distorsions, curve="convex", direction="decreasing")
elbow=kl.elbow

print('Elbow = {}'.format(elbow))
```

### <I>4. Analyze the clustering results</I>


```python
# We apply KMeans for the Elbow's value  ( in this case = 5)
kmeans = KMeans(n_clusters=elbow)
kmeans.fit(data)
y_kmeans = kmeans.predict(data)
df_kmeans = data.copy()
df_kmeans['cluster']=y_kmeans.astype(str)
```


```python
# Visualize the results
plt.style.use("dark_background")
g = sns.scatterplot(x='Return on Assets', y='Rev per share', hue=df_kmeans['cluster'].astype(int),
                    palette=['blue','green','yellow','orange','red'], data=df_kmeans)
plt.title("K-Means Clustering")

# Some random point we want to classify
plt.show()
```


```python
# see the centers
clusters_centers_df=pd.DataFrame(kmeans.cluster_centers_,columns=['Return on Assets','Rev per share'])
clusters_centers_df
```


```python
# See the clustering by Company
clustering_result=pd.DataFrame(zip(y_kmeans,data.index),columns=['Cluster','Company'])
clustering_result.set_index('Cluster').head()
```


```python
for cluster_num in list(clustering_result.set_index('Cluster').index.unique()):
    print (clustering_result.set_index('Cluster').loc[cluster_num].head())
```


```python
# Enrich Centers Df with the number of elements by Cluster
clusters_centers_df['Count']=clustering_result['Cluster'].value_counts().to_frame().rename(columns={'Cluster':'Count'})['Count']
clusters_centers_df.head()
```


```python
# Visualize Count of Elements by Cluster
plt.figure(figsize=(11,7))
plt.style.use("dark_background")
plt.bar(clusters_centers_df.index.values,clusters_centers_df['Count'])
plt.title("Count of Elements by Cluster")

plt.show()
```

## Portfolio Construction
### <I>1. From each cluster, choose the stocks with the highest Risk Adjusted Momentum </I>

We can use the [2 Year Historical Daily Prices](https://rapidapi.com/alphawave/api/stock-prices2?endpoint=apiendpoint_33fa1878-1727-4775-beeb-f6b0da5314fd) endpoint from the [AlphaWave Data Stock Prices API](https://rapidapi.com/alphawave/api/stock-prices2/endpoints) to pull in the two year historical prices.

To call this API with Python, you can choose one of the supported Python code snippets provided in the API console. The following is an example of how to invoke the API with Python Requests. You will need to insert your own <b>x-rapidapi-host</b> and <b>x-rapidapi-key</b> information in the code block below.


```python
#fetch 2 year daily return data

url = "https://stock-prices2.p.rapidapi.com/api/v1/resources/stock-prices/2y"

headers = {
    'x-rapidapi-host': "YOUR_X-RAPIDAPI-HOST_WILL_COPY_DIRECTLY_FROM_RAPIDAPI_PYTHON_CODE_SNIPPETS",
    'x-rapidapi-key': "YOUR_X-RAPIDAPI-KEY_WILL_COPY_DIRECTLY_FROM_RAPIDAPI_PYTHON_CODE_SNIPPETS"
    }

stock_frames = []

# for ticker in stock_tickers:
for ticker in tqdm(stock_tickers, position=0, leave=True, desc = "Retrieving AlphaWave Data Stock Info"):

    querystring = {"ticker":ticker}
    stock_daily_price_response = requests.request("GET", url, headers=headers, params=querystring)

    # Create Stock Prices DataFrame
    stock_daily_price_df = pd.DataFrame.from_dict(stock_daily_price_response.json())
    stock_daily_price_df = stock_daily_price_df.transpose()
    stock_daily_price_df = stock_daily_price_df.rename(columns={'Close':ticker})
    stock_daily_price_df = stock_daily_price_df[{ticker}]
    stock_frames.append(stock_daily_price_df)

combined_stock_price_df = pd.concat(stock_frames, axis=1, sort=True)
combined_stock_price_df = combined_stock_price_df.dropna(how='all')
combined_stock_price_df = combined_stock_price_df.fillna("")
combined_stock_price_df
```


```python
# Build of Portfolio of 50 stocks
number_of_stocks=50

# From each Cluster, we will pick the stocks with the highest risk adjusted momentum. The number of stocks from each cluster is proportional to its size
# Let's start by calculate the number of stocks to pick from each cluster
number_of_stocks_by_cluster=pd.DataFrame(round(number_of_stocks*clustering_result.groupby(by='Cluster').count()['Company']/clustering_result.count()['Company'],0))
number_of_stocks_by_cluster
```


```python
# From each Cluster, pick the stocks with the highest risk adjusted momentum.
as_of_date='2021-03-30'

portfolio_stocks=[]
for cluster_num in list(number_of_stocks_by_cluster.index):
    # for each cluster,get all the companies within this cluster
    list_stocks=list(clustering_result.set_index('Cluster').loc[cluster_num]['Company'])
    #get the number of stocks that we will pick for our portfolio
    number_stocks=number_of_stocks_by_cluster.loc[cluster_num]['Company']
    if number_stocks>0:
        # Compute the risk adjusted momentum for the past year
        last_year_date=pd.to_datetime(as_of_date)+ pd.offsets.DateOffset(years=-1)
        last_month_date=pd.to_datetime(as_of_date)+ pd.tseries.offsets.BusinessDay(offset = timedelta(days = -30))
        stock_price_last_year_date = last_year_date.strftime('%Y-%m-%d')
        stock_price_last_month_date = last_month_date.strftime('%Y-%m-%d')

        risk_adjusted_mom_frames = []
        for ticker in list_stocks:

            try:
                momentum = (combined_stock_price_df.loc[stock_price_last_month_date,][ticker] - \
                            combined_stock_price_df.loc[stock_price_last_year_date,][ticker]) / \
                            combined_stock_price_df.loc[stock_price_last_year_date,][ticker]

                annualized_volatility = np.log(combined_stock_price_df.loc[stock_price_last_year_date:as_of_date,][ticker] / \
                                               combined_stock_price_df.loc[stock_price_last_year_date:as_of_date,][ticker].shift(1)).dropna().std()*252**.5

                risk_adjusted_momentum = momentum / annualized_volatility

                # Create Dataframe
                df = pd.DataFrame({'Risk Adj MoM': risk_adjusted_momentum},
                                  index=[ticker])

                risk_adjusted_mom_frames.append(df)

            except:
                pass

        risk_adjusted_mom_df = pd.concat(risk_adjusted_mom_frames, ignore_index=False)
        risk_adjusted_mom_df["Rank"] = risk_adjusted_mom_df["Risk Adj MoM"].rank(ascending=False)
        risk_adjusted_mom_df[["Risk Adj MoM",
                              "Rank"]] = risk_adjusted_mom_df[["Risk Adj MoM",
                                                               "Rank"]].apply(pd.to_numeric)
        filtered_risk_adjusted_mom_df = risk_adjusted_mom_df[risk_adjusted_mom_df['Rank'] <= number_stocks]
        portfolio_stocks=portfolio_stocks+list(filtered_risk_adjusted_mom_df.index)

portfolio_stocks
```

### <I> 2. Compute Portfolio's Performance for 2021-Q2 </I>


```python
# Since we chose our portfolio stocks by end the of 2021-Q1, we will run it for 2021-Q2
end_date='2021-06-30'

# Compute the portfolio return. We will use equal weights for all the stocks
combined_stock_price_df = combined_stock_price_df.apply(pd.to_numeric)
s_p_500_daily_return = (combined_stock_price_df.loc[as_of_date:end_date,].pct_change().sum(axis=1).dropna()/len(combined_stock_price_df.columns)) + 1
cluster_portfolio_return=0
for stock in portfolio_stocks:
    daily_return = combined_stock_price_df.loc[as_of_date:end_date,][stock].pct_change().dropna() + 1
    cluster_portfolio_return=cluster_portfolio_return+(daily_return/len(portfolio_stocks))

# Create Dataframe
df = pd.DataFrame({'cluster_portfolio_return':cluster_portfolio_return,
                   'spx_index_return':s_p_500_daily_return},)

df.index.name = 'DATE'
return_ptf_index = df.dropna()
return_ptf_index = return_ptf_index.apply(pd.to_numeric)

return_ptf_index
```


```python
# Compute the annual volatility, sharpe ratio and annual excess return and plot the cumulative return
from math import sqrt

# compute the timeline for annualization
T = (pd.to_datetime(return_ptf_index['cluster_portfolio_return'].index[-1]) - pd.to_datetime(return_ptf_index['cluster_portfolio_return'].index[0])) / np.timedelta64(1, 'Y')

#portfolio Excess Return
portfolio_excess_return=round(100*(return_ptf_index['cluster_portfolio_return'].cumprod().iloc[-1]**(1/T) - 1),2)

#Portfolio Annual Volatility
portfolio_annual_volatility=round(100*return_ptf_index['cluster_portfolio_return'].std()*sqrt(252),2)

#Portfolio Sharpe Ratio
portfolio_sharpe_ratio=round((portfolio_excess_return)/portfolio_annual_volatility,2)

# Plot Results
print ("Portfolio Annual Excess Return : {}%".format(portfolio_excess_return))
print ("Portfolio Annual Volatility    : {}% ".format(portfolio_annual_volatility))
print ("Portfolio Sharpe Ratio         : {}".format(portfolio_sharpe_ratio))

plt.figure(figsize = (18,8))
ax = plt.gca()
plt.title("Portfolio Performance")
return_ptf_index['cluster_portfolio_return'].cumprod().plot(ax=ax,color=sns.color_palette()[1],linewidth=2)
return_ptf_index['spx_index_return'].cumprod().plot(ax=ax,color=sns.color_palette()[2],linewidth=2)
plt.ylabel("Cumulative Return %")
plt.legend()
plt.show()
```

You can repeat this analysis in order to build a portfolio that rebalances every end of Quarter.  Be sure the <code>as_of_date</code> and <code>end_date</code> variables are updated to reflect the most recent Quarter end and that these dates fit within the [2 Year Historical Daily Prices](https://rapidapi.com/alphawave/api/stock-prices2?endpoint=apiendpoint_33fa1878-1727-4775-beeb-f6b0da5314fd) endpoint from the [AlphaWave Data Stock Prices API](https://rapidapi.com/alphawave/api/stock-prices2/endpoints).

## References and Additional Resources
<ul>
  <li><a href ="https://hdonnelly6.medium.com/list/machine-learning-for-investing-7f2690bb1826"> Machine Learning for Investing </a></li>
  <li><a href ="https://www.cs.princeton.edu/sites/default/files/uploads/karina_marvin.pdf"> Princeton University: Creating Diversified Portfolios Using Cluster Analysis </a></li>
 <li> <a href ="https://scholarship.claremont.edu/cgi/viewcontent.cgi?article=3517&context=cmc_theses"> Scholarship @ Claremont: K-Means Stock Clustering Analysis Based on Historical Price Movements and Financial Ratios  </a> </li>
 <li> <a href ="https://realpython.com/k-means-clustering-python/"> Real Python: K-Means Clustering in Python: A Practical Guide  </a> </li>
 <li> <a href ="https://jakevdp.github.io/PythonDataScienceHandbook/"> Python Data Science Handbook </a></li>
 <li> <a href ="https://github.com/John-McClane/wp-experience-api"> GitHub John-McClane WP Experience API </a></li>
 <li> <a href ="https://scikit-learn.org/stable/modules/clustering.html"> SciKit Learn: Clustering </a></li>
 <li> <a href ="https://scikit-learn.org/stable/auto_examples/cluster/plot_kmeans_silhouette_analysis.html"> SciKit Learn: Selecting the number of clusters with silhouette analysis on K-Means clustering </a></li>
 <li> <a href ="https://scikit-learn.org/stable/auto_examples/text/plot_document_clustering.html"> SciKit Learn: Clustering text documents using k-Means </a></li>
 <li> <a href ="https://www.nature.com/articles/sdata2017171"> Open University Learning Analytics dataset </a></li>
 <li> <a href ="https://github.com/susilvaalmeida/machine-learning-andrew-ng"> Machine Learning with Andrew Ng </a></li>
 <li> <a href ="https://stanford.edu/~cpiech/cs221/handouts/kmeans.html"> Stanford K-Means </a></li>
 <li> <a href ="https://medium.com/analytics-vidhya/machine-learning-used-to-build-a-diversified-portfolio-k-means-clustering-ee91cb9ae59e"> Machine Learning used to build a Diversified Portfolio: K-Means Clustering </a></li>
 <li> <a href ="https://github.com/AlphaWaveData/Jupyter-Notebooks"> GitHub AlphaWave Data </a></li>
 <li> <a href ="https://towardsdatascience.com/text-clustering-using-k-means-ec19768aae48"> Text Clustering using K-means </a></li>
</ul>


```python

```
