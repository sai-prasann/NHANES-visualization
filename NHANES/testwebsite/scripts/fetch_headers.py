import requests
from bs4 import BeautifulSoup
import mysql.connector

# Database connection settings
db_config = {
    'host': 'localhost',  
    'user': 'root',  
    'password': '',  
    'database': 'testwebsite'  
}

# Function to extract doc_code and description
def extract_doc_code_and_description(full_text):
    split_index = full_text.find(' - ')
    
    if split_index != -1:
        # Extract doc_code before the first ' - ' and description after it
        doc_code = full_text[:split_index].strip()
        description = full_text[split_index + 3:].strip()
    else:
        doc_code = None
        description = full_text.strip()
    
    return doc_code, description



# Function to check if doc_code already exists in the database
def doc_code_exists(cursor, doc_code):
    check_query = "SELECT COUNT(*) FROM headers_description WHERE doc_code = %s"
    cursor.execute(check_query, (doc_code,))
    count = cursor.fetchone()[0]
    return count > 0


# Function to insert scraped data into the MySQL database
def insert_data_to_db(data_list):
    try:
        
        connection = mysql.connector.connect(**db_config)
        cursor = connection.cursor()

        cursor.execute('''CREATE TABLE IF NOT EXISTS headers_description (
                          id INT AUTO_INCREMENT PRIMARY KEY,
                          description TEXT,
                          doc_code VARCHAR(255) UNIQUE)''')  

        insert_query = "INSERT INTO headers_description (description, doc_code) VALUES (%s, %s)"
        for item in data_list:
            doc_code, description = extract_doc_code_and_description(item)

            if doc_code and not doc_code_exists(cursor, doc_code):
                cursor.execute(insert_query, (description, doc_code))

        connection.commit()

    except mysql.connector.Error as err:
        print(f"Error: {err}")
    finally:
        # Close the connection
        if connection.is_connected():
            cursor.close()
            connection.close()


# Function to fetch docs_url from datasets
def fetch_dataset_urls():
    connection = None 
    try:
    
        connection = mysql.connector.connect(**db_config)
        cursor = connection.cursor()

        # Fetch the docs_url from the datasets table
        fetch_query = "SELECT docs_url FROM datasets"
        cursor.execute(fetch_query)
        urls = cursor.fetchall()  

        return [url[0] for url in urls]  # Return list of URLs (since each url is a tuple)

    except mysql.connector.Error as err:
        print(f"Error fetching URLs: {err}")
        return []

    finally:
        if connection and connection.is_connected():
            cursor.close()
            connection.close()


# Function to scrape data from a given URL
def scrape_data_from_url(url):
    scraped_data = []
    try:
        response = requests.get(url)

        if response.status_code == 200:
            soup = BeautifulSoup(response.text, 'html.parser')

            outer_li_tags = soup.find_all('li')

            for outer_li in outer_li_tags:
                nested_ul = outer_li.find('ul')

                if nested_ul:
                    inner_li_tags = nested_ul.find_all('li')

                    for inner_li in inner_li_tags:
                        a_tag = inner_li.find('a')
                        if a_tag:
                            scraped_data.append(a_tag.text.strip())

    except Exception as e:
        print(f"Error scraping {url}: {e}")

    return scraped_data

# Main function to scrape and insert data from all dataset URLs
def main():
    dataset_urls = fetch_dataset_urls()

    for url in dataset_urls:
        print(f"Scraping data from {url}")
        scraped_data = scrape_data_from_url(url)
        insert_data_to_db(scraped_data)

if __name__ == "__main__":
    main()
