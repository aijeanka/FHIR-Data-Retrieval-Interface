# FHIR Data Retrieval Interface

**Author**: Aizhan Uteubayeva  
**Published**: June 17, 2024

## Project Overview

This project provides an interface for retrieving and displaying FHIR (Fast Healthcare Interoperability Resources) data. It allows users to select a patient and retrieve various types of data such as encounters, care plans, medication orders, observations, allergies, conditions, procedures, and appointments.

## Table of Contents
- [Installation](#installation)
- [Usage](#usage)
- [Features](#features)
- [Dependencies](#dependencies)
- [License](#license)

## Installation

1. Clone the repository to your local machine.
2. Ensure you have internet access to load the necessary libraries and access the FHIR server.

## Usage

1. Open the `modified_get_data_Aizhan.html` file in a web browser.
2. Enter the root URL of the FHIR server.
   - Default: `https://fhir-open.cerner.com/dstu2/ec2458f2-1e24-41c8-b71b-0e701af7583d/`
3. Select a patient from the dropdown menu.
4. Select the type of data you want to retrieve (e.g., Patient, Encounters, Care Plan, Meds, Observations, Allergies, Conditions, Procedures, Appointments).
5. Click the "click" button to retrieve and display the data.
6. The retrieved data will be displayed in the text area and in a table format if applicable.

## Features

- Retrieve various types of FHIR data for selected patients.
- Display medication details including drug name, timing, route, and dosage in a table format.
- Handle and display errors if the data retrieval fails.

## Dependencies

- [jQuery](https://code.jquery.com/jquery-3.6.0.min.js)

## License

This project is licensed under the MIT License.
