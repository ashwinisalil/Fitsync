```javascript
/* =========================================================
   GYMCONNECT OWNER MEMBERS
   Member Search
========================================================= */

document.addEventListener(
    "DOMContentLoaded",
    function () {

        const searchInput =
            document.getElementById(
                "memberSearch"
            );

        const table =
            document.getElementById(
                "membersTable"
            );


        if (
            !searchInput ||
            !table
        ) {

            return;

        }


        searchInput.addEventListener(
            "input",
            function () {


                const searchValue =
                    this.value
                    .toLowerCase()
                    .trim();


                const rows =
                    table.querySelectorAll(
                        "tbody tr"
                    );


                rows.forEach(
                    function (row) {


                        const rowText =
                            row.textContent
                            .toLowerCase();


                        if (
                            rowText.includes(
                                searchValue
                            )
                        ) {

                            row.style.display =
                                "";

                        } else {

                            row.style.display =
                                "none";

                        }

                    }
                );

            }
        );

    }
);
```
