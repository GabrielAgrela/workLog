<?php
// Initialize the session
session_start();
require_once "config.php";

// Check if the user is logged in, if not then redirect him to login page
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION["admin"] !== true)
{
    header("location: logout.php");
    exit;
}

//total minutes worked, incremented by table
$totalMinutes=0;

//welcome user
echo "<h1 style='margin-bottom: 1%'>Olá, ".$_SESSION["username"]."!</h1>";

?>

<!-- START HTML-->
<html>
	<head>
		<meta charset="UTF-8">
		<title>Admin</title>
		<style type="text/css">
			table {margin-bottom: 5% !important;}
	        body{color: #f0f0f0 !important; background-color: #2a2a2a !important; font: 18px sans-serif; position: relative; padding: 5%; }
	        .wrapper{ width: 350px; padding: 20px; }
	    </style>
		<meta name="viewport" content="width=device-width, initial-scale=1">
		<link rel="icon" type="image/png" href="media\favicon.png">
		<script  src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/1.3.4/jspdf.min.js"></script>
		<script src="https://code.jquery.com/jquery-3.3.1.slim.min.js" integrity="sha384-q8i/X+965DzO0rT7abK41JStQIAqVgRVzpbzo5smXKp4YfRvH+8abtTE1Pi6jizo" crossorigin="anonymous"></script>
		<script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.7/umd/popper.min.js" integrity="sha384-UO2eT0CpHqdSJQ6hJty5KVphtPhzWj9WO1clHTMGa3JDZwrnQq4sF86dIHNDz0W1" crossorigin="anonymous"></script>
		<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/js/bootstrap.min.js" integrity="sha384-JjSmVgyd0p3pXB1rRibZUAYoIIy6OrQ6VrjIEaFf/nJGzIxFDsf4x0xIM+B07jRM" crossorigin="anonymous"></script>
		<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css" rel='stylesheet' media='all'>
		<link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css" integrity="sha384-ggOyR0iXCbMQv3Xipma34MD+dH/1fQ784/j6cY/iJTQUOhcWr7x9JvoRxT2MZw1T" crossorigin="anonymous" rel='stylesheet' media='all'>
	</head>
	<body>
		<form action="logout.php">
		    <input class="btn btn-danger" type="submit" value="LogOut" />
		</form>
		<br>
		<?php
			//select worklog data from this user
			$sql = "SELECT id,id_user, description, start, finish, paid FROM worklog ORDER BY id_user,id";
			//for each row of data in worklog table, write a row in the html table
			if($stmt = mysqli_prepare($link, $sql))
			{
				if(mysqli_stmt_execute($stmt))
				{
					mysqli_stmt_store_result($stmt);
					if(mysqli_stmt_num_rows($stmt) >= 1)
					{
						$i=0;//number of total rows
						$lastRowId_user=-1;
						$endTable=0;
						mysqli_stmt_bind_result($stmt, $id, $id_user, $description, $start, $finish, $paid);
						while (mysqli_stmt_fetch($stmt))
						{
							$i++;
							//if last row id_user is different from this row's id_user then start a new table
							if($lastRowId_user!=$id_user)
							{
								//at the beggining of each row (except the first), close the table and print total work
								if($i!=1)
								{
									?>
											<tr>
										      <td colspan="5"></td>
										      <td colspan="2">
												<?php echo floor($totalMinutes/60)." h e ". $restMinutes." m";?>
												<hr>
													<span>
														<a href="https://meusalario.pt/salario/salariominimo">9 109,38€ de salário mínimo anual na madeira de 2020</a> /
														<a href="https://www.dias-uteis.pt/dias-uteis_feriados_2020.htm">253 dias úteis</a>
															/ 8 horas diárias * <?php echo floor($totalMinutes/60);?> horas = <?php echo round(9109.38/253/8*floor($totalMinutes/60));?>€
													</span>
												</td>

										    </tr>
											</table>

										</div>
									<?php
								}
								$totalMinutes=0;
								?>
								<div class="table-responsive">
									<table class = "table table-striped table-dark table-hover">
										<thead>
											<tr>
												<th scope="col" style="width: 3%">#</th>
												<th scope="col" style="width: 8%">username</th>
												<th scope="col" style="width: 52%">descrição</th>
												<th scope="col"style="width: 12.5%">inicio</th>
												<th scope="col"style="width: 12.5%">fim</th>
												<th scope="col"style="width: 10%">tempo total</th>
												<th scope="col"style="width: 2%">paid</th>
											</tr>
										</thead>
								<?php
							}
							$lastRowId_user=$id_user;

							echo "<tr>";
							echo "<th scope='row'>".$id."</th>";

							//query to get the username from the FK in the worklog
							$sql = "SELECT username FROM users where id = ?";
							if($stmtName = mysqli_prepare($link, $sql))
							{
								mysqli_stmt_bind_param($stmtName, "i", $id_user);
								if(mysqli_stmt_execute($stmtName))
								{
									mysqli_stmt_store_result($stmtName);
									mysqli_stmt_bind_result($stmtName, $username);
									while (mysqli_stmt_fetch($stmtName))
										echo "<td scope='row'>".$username."</td>";
								}
							}

							echo "<td>$description</td>";
							echo "<td>".date('d-m-Y H:i', strtotime($start))."</td>";

							// if start == finish, then it means the work isn't finished
							if ($start == $finish)
								echo "<td>ONGOING</td>";
							else
								echo "<td>".date('d-m-Y H:i', strtotime($finish))."</td>";//formating datetime to remove seconds

							//operations to get the time difference between finish worklog and start worklog in minutes (work time)
							$datetime1 = strtotime($start);
							$datetime2 = strtotime($finish);
							$secs = $datetime2 - $datetime1;
							$minutes = $secs / 60;
							echo "<td>".floor($minutes)." m</td>";
							if ($paid == 0)
								echo "<td><i class='fa fa-close' style='font-size:24px;color:red'></td>";
							else
								echo "<td><i class='fa fa-check' style='font-size:24px;color:green'></i></td>";

							$totalMinutes=$totalMinutes + floor($minutes);
							$restMinutes=$totalMinutes - floor($totalMinutes/60)*60;
							echo "</tr>";

							if($endTable==1)
							{

							}
						}
					}
				}
				else
				{
					echo "Oops! Something went wrong. Please try again later.";
				}
					mysqli_stmt_close($stmt);
			}
			?>
		<!-- close last table and print total work!-->
			<tr>
		      <td colspan="5"></td>
		      <td colspan="2"><?php echo floor($totalMinutes/60)." h e ". $restMinutes." m";?>
			  <hr>
				  <span>
					  <a href="http://meusalario.pt/salario/salariominimo">9 109,38€ de salário mínimo anual na madeira de 2020</a> /
					  <a href="https://www.dias-uteis.pt/dias-uteis_feriados_2020.htm">253 dias úteis</a>
					  / 8 horas diárias * <?php echo floor($totalMinutes/60);?> horas = <?php echo round(9109.38/253/8*floor($totalMinutes/60));?>€
				  </span>
			  <hr>
			  <button class="btn btn-warning" onclick="printDiv()">Gerar PDF</button>
			  </td></td>
		    </tr>
			</table>
		</div>

	</body>
	<script>
	function printDiv() {
        let printContents, popupWin;
        printContents = document.getElementsByClassName("table-responsive")[0].innerHTML;
        popupWin = window.open('', '_blank', 'top=0,left=0,height=100%,width=auto');
        popupWin.document.open();
        popupWin.document.write(`
          <html>
            <head>
           	<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css" rel='stylesheet' media='all'>
			<link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css" integrity="sha384-ggOyR0iXCbMQv3Xipma34MD+dH/1fQ784/j6cY/iJTQUOhcWr7x9JvoRxT2MZw1T" crossorigin="anonymous">
            </head>
            <style>

            </style>
        <body onload="window.print();window.close()">${printContents}</body>
          </html>`
        );
        popupWin.document.close();
    }
  	</script>
</html>
<?php
?>
