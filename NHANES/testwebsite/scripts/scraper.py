import requests
from selectolax.lexbor import LexborHTMLParser
import signal
import traceback
import json


SCRAPER_BASE_URL = "https://wwwn.cdc.gov/nchs/nhanes/search/datapage.aspx?Component="
COMPONENTS = ["Demographics", "Dietary", "Examination", "Laboratory", "Questionnaire"]

# Dataset class to store scraped data
class Dataset:
    def __init__(self, years, component, description, docs_url, data_url, id, is_available, code):
        self.years = years
        self.component = component
        self.description = description
        self.docs_url = docs_url
        self.data_url = data_url
        self.id = id
        self.is_available = is_available
        self.code = code

# Scraper class
class Scraper:
    def __init__(self):
        self.datasets = []
        self.id_counter = 1
        self.is_terminated = False

    def parse_row(self, td_list, component):
        try:
            base_url = "https://wwwn.cdc.gov"
            docs_anchor = td_list[2].css_first("a")
            data_anchor = td_list[3].css_first("a")

            if not (docs_anchor and data_anchor):
                # Missing anchors
                return

            # Only process .xpt files
            if not data_anchor.attrs['href'].strip().lower().endswith(".xpt"):
                # Non XPT file found
                return

            description = td_list[1].text().strip().replace('"', '')

            dataset = Dataset(
                years=td_list[0].text().strip(),
                component=component,
                description=description,
                docs_url=f"{base_url}{docs_anchor.attrs['href'].strip()}",
                data_url=f"{base_url}{data_anchor.attrs['href'].strip()}",
                id=self.id_counter,
                is_available=False,
                code=data_anchor.attrs['href'].split('/')[-1].split('.')[0]
            )

            self.datasets.append(dataset)
            self.id_counter += 1
        except Exception as e:
            print(f"Error parsing row: {traceback.format_exc()}")

    def scrape_component(self, component):
        if self.is_terminated:
            return

        try:
            # Scrape the component page
            response = requests.get(f"{SCRAPER_BASE_URL}{component}")
            response.raise_for_status()
            parser = LexborHTMLParser(response.text)

            table_body = parser.css_first("table > tbody")
            if not table_body:
                print(f"No table found for component: {component}")
                return

            for row in table_body.css("tr"):
                if self.is_terminated:
                    return
                self.parse_row(row.css("td"), component)

        except Exception as e:
            print(f"Error scraping component {component}: {traceback.format_exc()}")

    def scrape(self):
        for component in COMPONENTS:
            if self.is_terminated:
                break
            self.scrape_component(component)

        return [dataset.__dict__ for dataset in self.datasets]

    def terminate(self):
        self.is_terminated = True

# Signal handling for graceful shutdown
def signal_handler(signum, frame):
    global scraper
    if scraper:
        scraper.terminate()

signal.signal(signal.SIGTERM, signal_handler)

# Main function
scraper = None

def main():
    global scraper
    scraper = Scraper()
    result = scraper.scrape()

    if result:
        #Print instead of return so data can be returned and used in PHP
        print(json.dumps(result))
    else:
        print("No data scraped.")

if __name__ == "__main__":
    main()
