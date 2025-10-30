<!DOCTYPE html>
<html>
<head>
    <style>
        .footer {
            text-align: center;
            border-top: 1px solid #ccc;
            padding-top: 10px;
            margin-top: 20px;
            font-size: 10px;
            color: #666;
        }
    </style>
</head>
<body>
    <div class="footer">
        <p>Page <span class="pageNumber"></span> of <span class="totalPages"></span></p>
        <p>Generated on {{ now()->format('F j, Y \a\t g:i A') }}</p>
    </div>
</body>
</html>

