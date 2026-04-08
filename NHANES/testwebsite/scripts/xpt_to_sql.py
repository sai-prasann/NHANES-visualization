import sys
import pandas as pd
from sqlalchemy import create_engine, text
import requests
import os
from urllib.parse import urlparse
import traceback

print("imported")

def download_xpt_file(url, output_path):
    try:
        response = requests.get(url)
        response.raise_for_status() 
        with open(output_path, 'wb') as f:
            f.write(response.content)
        print(f"Downloaded the XPT file to {output_path}")
    except Exception as e:
        print(f"Error downloading XPT file: {str(e)}")
        raise

def xpt_to_dataframe(xpt_file):
    df = pd.read_sas(xpt_file)
    
    # Keep SEQN column and turn variables and values into row form
    df = df.melt(id_vars=["SEQN"], var_name="variable_id", value_name="value")
    print(f"Successfully converted XPT file to DataFrame. Shape: {df.shape}")
    return df

def main(xpt_url):
    xpt_file = urlparse(xpt_url).path.split("/")[-1]

    try:
        download_xpt_file(xpt_url, xpt_file)
        
        # Convert the XPT file to a pandas DataFrame
        df = xpt_to_dataframe(xpt_file)

        # Output the df to a CSV to be uploaded to the DB
        df.to_csv("dataset.csv", sep=",", index=False, header=False)


    except Exception as e:
        print(f"Error in main process: {str(e)}")
        print(traceback.format_exc())
        sys.exit(1)
    finally:
        # Clean up the downloaded XPT file
        if os.path.exists(xpt_file):
            os.remove(xpt_file)
            print(f"Deleted the temporary XPT file: {xpt_file}")

if __name__ == "__main__":
    xpt_url = sys.argv[1]

    main(xpt_url)

    if len(sys.argv) != 2:
        print("Usage: python xpt_to_sql.py <xpt_url>")
        sys.exit(1)
