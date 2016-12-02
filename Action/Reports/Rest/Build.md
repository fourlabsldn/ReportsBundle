## Sample Post

```json
{
    "currentPage": 2,
    "maxResultsPerPage": 20,
    "reportBuilderId": "user_report_builder",
    "reportColumns": ["id", "firstname", "lastname"],
    "reportSortColumns": {
      "id": "ASC"
    },
    "rulesJsonString": "{\n  \"condition\": \"AND\",\n  \"rules\": [\n    {\n      \"id\": \"firstname\",\n      \"field\": \"firstname\",\n      \"type\": \"string\",\n      \"input\": \"text\",\n      \"operator\": \"is_not_null\",\n      \"value\": null\n    }\n  ]\n}"
}   
```

## Sample Response
```json
{
  "data": {
    "columns": [
      "id",
      "firstname",
      "lastname"
    ],
    "reportColumnsHumanReadable": [
      "ID",
      "First Name",
      "Last Name"
    ],
    "results": [
      {
        "id": "1",
        "firstname": "Test",
        "lastname": "Test"
      }
    ]
  },
  "state": {
    "currentPage": 2,
    "maxResultsPerPage": 20,
    "resultsInThisPage": 1,
    "totalResults": 21,
    "totalPages": 2
  },
  "links": {
    "prev": {
      "baseUrl": "https://example.com/example_prefix/reports_build",
      "data": {
        "currentPage": 1,
        "maxResultsPerPage": 20,
        "reportBuilderId": "user_report_builder",
        "reportColumns": ["id", "firstname", "lastname"],
        "reportSortColumns": {
          "id": "ASC"
        },
        "rulesJsonString": "{\n  \"condition\": \"AND\",\n  \"rules\": [\n    {\n      \"id\": \"firstname\",\n      \"field\": \"firstname\",\n      \"type\": \"string\",\n      \"input\": \"text\",\n      \"operator\": \"is_not_null\",\n      \"value\": null\n    }\n  ]\n}"
      }
    }
  }
}
```