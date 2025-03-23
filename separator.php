<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Excel Separator</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="container mt-5">
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container-fluid">
            <a class="navbar-brand" href="#">Excel Comparer and separator</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="btn btn-primary me-2" href="index.php">Comparer</a>
                    </li>
                    <li class="nav-item">
                        <a class="btn btn-success" href="separator.php">Separator</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>
    <div class="card shadow p-4">
        <h2 class="text-center">Upload Excel Files Separator</h2>
        <form id="uploadForm" enctype="multipart/form-data">
            <label class="form-label fw-bold">Select Transaction Type:</label>
            <div class="d-flex gap-3">
                <div class="form-check">
                    <input class="form-check-input" type="radio" checked name="transaction_type" value="FAKTUR" id="faktur" required>
                    <label class="form-check-label" for="faktur">FAKTUR</label>
                </div>
                <div class="form-check">
                    <input class="form-check-input" type="radio" name="transaction_type" value="RETUR" id="retur" required>
                    <label class="form-check-label" for="retur">RETUR</label>
                </div>
            </div>
            <div class="mb-3">
                <label class="form-label">Excel 1 (Book):</label>
                <input type="file" class="form-control" id="file1" name="file1" required>
            </div>
            <button type="submit" class="btn btn-primary w-100">Process Files</button>
        </form>
        <div class="text-center mt-3">
            <a id="downloadLink" class="btn btn-success" style="display: none;" download>Download Processed File</a>
        </div>
    </div>
    
    <script>
        document.getElementById("uploadForm").addEventListener("submit", function(event) {
            event.preventDefault();
            let formData = new FormData();
            formData.append("file1", document.getElementById("file1").files[0]);
            const selectedRB = document.querySelector('input[name="transaction_type"]:checked').value;
            formData.append("transaction_type", selectedRB);

            var url = "";
            var fileName = "";
            if (selectedRB === "FAKTUR")
            {
                url="php/separator.php"
            }
            else if (selectedRB === "RETUR")
            {
                url="php/separator-retur.php"
            }
            fetch(url, {
                method: "POST",
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.download) {
                    let downloadLink = document.getElementById('downloadLink');
                    downloadLink.href = "/php/download_zip.php?file=" + data.download;
                    downloadLink.style.display = 'inline-block';
                    downloadLink.textContent = "Download Processed File";
                    alert("Success! Your file is ready to download.");
                }
            })
            .catch(error => console.error("Error:", error));
        });
    </script>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>