<?php
	trait db{
		public function pdo(){
			$db_host = db_host;
			$db_nome = db_nome;
			$db_usuario = db_usuario;
			$db_senha = db_senha;

			try{
				return $pdo = new PDO("mysql:host={$db_host};dbname={$db_nome}", $db_usuario, $db_senha);
				$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
			}catch(PDOException $e){
				echo "Erro ao conectar-se: ".$e->getMessage();
				exit();
			}
		}
	}

	class website{

		public static function get_explode(){
			$url = (isset($_GET['pagina'])) ? $_GET['pagina'] : 'inicio';
			return $explode = explode('/', $url);
		}

		public static function get_data(){
			date_default_timezone_set('America/Sao_Paulo');
			return date('d/m/Y');
		}

		public static function website_paginacao(){
			$url = (isset($_GET['pagina'])) ? $_GET['pagina'] : 'inicio';
			$explode = explode('/', $url);
			$dir = 'pags/php/';
			$ext = '.php';

			if(file_exists($dir.$explode['0'].$ext)){
				include($dir.$explode['0'].$ext);
			}else{
				echo "Página não encontrada";
			}
		}

		public function website_limitaCaracteres($titulo){
			if(strlen($titulo) <= 27){
				return $titulo;
			}else{
				return mb_substr($titulo, 0, 27, 'UTF-8' )."...";
			}
		}

		public static function website_selectBanco($banco){
			$bancos = ['images/template/banco_santander.jpg',
						'images/template/banco_caixa.png',
						'images/template/banco_itau.png',
						'images/template/banco_bb.gif',
						'images/template/banco_bradesco.png'];


			switch($banco){
				case 0:
				echo "<img src='{$bancos[0]}' width='180' height='50' />";
				break;

				case 1:
				echo "<img src='{$bancos[1]}' width='180' height='50' />";
				break;

				case 2:
				echo "<img src='{$bancos[2]}' width='180' height='50' />";
				break;

				case 3:
				echo "<img src='{$bancos[3]}' width='180' height='50' />";
				break;

				case 4:
				echo "<img src='{$bancos[4]}' width='180' height='50' />";
				break;
			}
		}

		public static function website_getUniqPaymentMP($pagamento){
            $preference = new MercadoPago\Preference();
            $preco = str_replace(',', '.', $pagamento[2]);
            #item
            $item = new MercadoPago\Item();
            $item->id = $pagamento[0];
            $item->title = $pagamento[1];
            $item->quantity = 1;
            $item->unit_price = $preco;
 
            #preference
            $preference->items = array($item);
 
            #Id de referencia
            $preference->external_reference = $pagamento[3];
 
            #salva a preferencia
            $preference->save();
 
            echo "<a href='{$preference->sandbox_init_point}' class='btn btn-outline-success'>Pagar Agora</a>";
        }

        public static function website_verificaIsLogado(){
        	if(!isset($_SESSION['userEmail'])){
        		self::website_direciona("inicio");
        		exit();
        	}
        }

		public static function website_admin_paginacao(){
			$url = (isset($_GET['pagina'])) ? $_GET['pagina'] : 'login';
			$explode = explode('/', $url);
			$dir = 'pags/php/';
			$ext = '.php';

			$isAdmin = self::website_getDadosCliente("isadmin");

			if(file_exists($dir.$explode['0'].$ext)){
				if(isset($_SESSION['userEmail']) && $isAdmin == 1){
					include($dir.$explode['0'].$ext);
				}else{
					include($dir."login".$ext);
				}		
			}else{
				echo "Página não encontrada!";
			}
		}


		public static function website_admin_verificaLogin(){
			$isAdmin = self::website_getDadosCliente("isadmin");
			if(isset($_SESSION['userEmail']) && $isAdmin == 1){
				self::website_direciona("dashboard");
				exit();
			}
		}

		public static function website_categorias(){
			$pdo = db::pdo();

			try{
				$stmt = $pdo->prepare("SELECT * FROM categorias ORDER BY categoria ASC");
				$stmt->execute();
				$total = $stmt->rowCount();

				if($total > 0){
					while ($dados = $stmt->fetch(PDO::FETCH_ASSOC)) {
						echo "<div class='content-left-menu'>
					<div class='title-content-left-menu'><a href='categoria/{$dados['id']}'>{$dados['categoria']}</a></div>
					<div class='content'>
					  <ul>".self::website_subcategorias($dados['id'])." 
					  </ul>
					</div>
				</div>";
					}
				}
			}catch(PDOException $e){
				return $e->getMessage();
			}
		}

		public static function website_subcategorias($id){
			$pdo = db::pdo();

			try{
				$stmt = $pdo->prepare("SELECT * FROM subcategorias WHERE id_categoria = :id_categoria ORDER BY subcategoria ASC");
				$stmt->execute([':id_categoria' => $id]);
				$total = $stmt->rowCount();

				if($total > 0){
					while ($dados = $stmt->fetch(PDO::FETCH_ASSOC)) {
						return "<li class='category'><a href='subcategoria/{$dados['id']}'>{$dados['subcategoria']}</a></li>";
					}
				}
			}catch(PDOException $e){
				return $e->getMessage();
			}
		}

		public static function website_navLogin(){
			if(isset($_SESSION['userEmail'])){
				$clientes = new clientes();

				echo "<i class='fas fa-user'></i> <a href='dashboard'> Bem vindo <b>{$clientes->nome}</b></a> | <a href='sair'><i class='fas fa-sign-out-alt'></i> Sair</a>";
			}else{
				echo "<a href='login'>Entra</a> | <a href='cadastro'>Cadastrar</a>";
			}
		}

		public static function website_autenticaLogin(){
			if(isset($_POST['log']) && $_POST['log'] == "in"){
				$pdo = db::pdo();

				$stmt = $pdo->prepare("SELECT * FROM clientes WHERE email = :email AND senha = :senha");
				$stmt->execute(array(':email' => $_POST['email'], ':senha' => $_POST['senha']));
				$total = $stmt->rowCount();


				if($total <= 0){
					echo "<span class='text-danger'>Email ou senha inválidos</span>";
				}else{
					$dados = $stmt->fetch(PDO::FETCH_ASSOC);
					echo "<span class='text-success'>Logado com sucesso!</span>";
					$_SESSION['userEmail'] = $dados['email'];
					self::website_direciona("dashboard");
				}
			}
		}

		public static function website_direciona($url){
			echo "<meta http-equiv='refresh' content='2; url={$url}'>";
		}

		public static function website_logout(){
			session_destroy();
			self::website_direciona("login");
		}

		public static function website_produtos_home(){
			$pdo = db::pdo();

			$stmt = $pdo->prepare("SELECT * FROM produtos");
			$stmt->execute();
			$total = $stmt->rowCount();

			if($total > 0){
				while ($dados = $stmt->fetch(PDO::FETCH_ASSOC)) {	
					echo "<div class='col-sm-4'>
			  <div class='product'>
			      <div class='p-title'>".self::website_limitaCaracteres($dados['nome'])."</div>
			      <div class='p-content'>
			        <img src='{$dados['foto']}'>
			        <div class='price'>
			          <span class='cf'>R$</span> 
			          <span class='prc'>{$dados['preco']}";
			          if($dados['tipo_fatura'] == 1){
							echo "<small>/Mes</small>";
						}
			          echo "</span>
			          </div>
			      </div>
			      <div class='p-footer'>
			        <a href='comprar/{$dados['id']}'><i class='fas fa-shopping-cart'></i> Comprar</a>
			        <span class='float-right'><a href='produto/{$dados['id']}'><i class='fas fa-plus'></i> Detalhes</a></span>
			      </div>
			    </div><br>
			</div>";
				}
			}
		}

		public static function website_getInfosCategoria($id){
			if(isset($id)){
				$pdo = db::pdo();

				$stmt = $pdo->prepare("SELECT * FROM categorias WHERE id = :id");
				$stmt->execute([':id' => $id]);
				$total = $stmt->rowCount();

				if($total > 0){
					$dados = $stmt->fetch(PDO::FETCH_ASSOC);

					echo "<div class='r-title'>Eshop / Categoria / {$dados['categoria']}</div>
					<br>
					<div class='r-description'>
					{$dados['descricao']}
					</div>";
				}
			}
		}

		public static function website_getInfosSubCategoria($id){
			if(isset($id)){
				$pdo = db::pdo();

				$stmt = $pdo->prepare("SELECT * FROM subcategorias WHERE id = :id");
				$stmt->execute([':id' => $id]);
				$total = $stmt->rowCount();

				if($total > 0){
					$dados = $stmt->fetch(PDO::FETCH_ASSOC);

					echo "<div class='r-title'>Eshop / SubCategoria / {$dados['subcategoria']}</div>
					<br>
					<div class='r-description'>
					{$dados['descricao']}
					</div>";
				}
			}
		}

		public static function website_getIdFromCategoria($idsub, $val){
			$pdo = db::pdo();

			$stmt = $pdo->prepare("SELECT * FROM subcategorias WHERE id = :id");
			$stmt->execute([':id' => $idsub]);
			$total = $stmt->rowCount();

			if($total > 0){
				$dados = $stmt->fetch(PDO::FETCH_ASSOC);

				return $dados[$val];
			}
		}

		public static function website_getIdFromSubCategoria($idsub, $val){
			$pdo = db::pdo();

			$stmt = $pdo->prepare("SELECT * FROM subcategorias WHERE id_categoria = :id");
			$stmt->execute([':id' => $idsub]);
			$total = $stmt->rowCount();

			if($total > 0){
				$dados = $stmt->fetch(PDO::FETCH_ASSOC);

				return $dados[$val];
			}
		}

		public static function website_produtoFromCategoria($id){

			if(isset($id)){

				$pdo = db::pdo();
				$id_categoria = self::website_getIdFromSubCategoria($id, "id");

				$stmt = $pdo->prepare("SELECT * FROM produtos WHERE categoria = :categoria");
				$stmt->execute([':categoria' => $id_categoria]);
				$total = $stmt->rowCount();

				if($total > 0){
					while ($dados = $stmt->fetch(PDO::FETCH_ASSOC)) {	
						echo "<div class='col-sm-4'>
				  <div class='product'>
				      <div class='p-title'>".self::website_limitaCaracteres($dados['nome'])."</div>
				      <div class='p-content'>
				        <img src='{$dados['foto']}'>
				        <div class='price'>
				          <span class='cf'>R$</span> 
				          <span class='prc'>{$dados['preco']}";
				          if($dados['tipo_fatura'] == 1){
								echo "<small>/Mes</small>";
							}
				          echo "</span>
				          </div>
				      </div>
				      <div class='p-footer'>
				        <a href='comprar/{$dados['id']}'><i class='fas fa-shopping-cart'></i> Comprar</a>
				        <span class='float-right'><a href='produto/{$dados['id']}'><i class='fas fa-plus'></i> Detalhes</a></span>
				      </div>
				    </div><br>
				</div>";
					}
				}
			}
		}

		public static function website_produtoFromSubCategoria($id){

			if(isset($id)){

				$pdo = db::pdo();
				$id_categoria = self::website_getIdFromCategoria($id, "id");

				$stmt = $pdo->prepare("SELECT * FROM produtos WHERE categoria = :categoria");
				$stmt->execute([':categoria' => $id_categoria]);
				$total = $stmt->rowCount();

				if($total > 0){
					while ($dados = $stmt->fetch(PDO::FETCH_ASSOC)) {	
						echo "<div class='col-sm-4'>
				  <div class='product'>
				      <div class='p-title'>".self::website_limitaCaracteres($dados['nome'])."</div>
				      <div class='p-content'>
				        <img src='{$dados['foto']}'>
				        <div class='price'>
				          <span class='cf'>R$</span> 
				          <span class='prc'>{$dados['preco']}";
				          if($dados['tipo_fatura'] == 1){
								echo "<small>/Mes</small>";
							}
				          echo "</span>
				          </div>
				      </div>
				      <div class='p-footer'>
				        <a href='comprar/{$dados['id']}'><i class='fas fa-shopping-cart'></i> Comprar</a>
				        <span class='float-right'><a href='produto/{$dados['id']}'><i class='fas fa-plus'></i> Detalhes</a></span>
				      </div>
				    </div><br>
				</div>";
					}
				}
			}
		}

		public function website_getDadosFatura($id, $val){
			$pdo = db::pdo();

				$stmt = $pdo->prepare("SELECT * FROM faturas WHERE id = :id");
				$stmt->execute([':id' => $id]);

				$dados = $stmt->fetch(PDO::FETCH_ASSOC);

				return $dados[$val];
		}

		public static function website_getDadosCliente($val){
			if(isset($_SESSION['userEmail'])){
				$pdo = db::pdo();

				$stmt = $pdo->prepare("SELECT * FROM clientes WHERE email = :email");
				$stmt->execute([':email' => $_SESSION['userEmail']]);

				$dados = $stmt->fetch(PDO::FETCH_ASSOC);

				return $dados[$val];
			}
		}

		public function website_getDetailsCompra($id_fatura, $val){
			$pdo = db::pdo();

				$stmt = $pdo->prepare("SELECT * FROM compras WHERE id_fatura = :id_fatura");
				$stmt->execute([':id_fatura' => $id_fatura]);

				$dados = $stmt->fetch(PDO::FETCH_ASSOC);

				return $dados[$val];
		}

		public function website_getDadosCompra($explode){
			if(isset($explode)){
				$pdo = db::pdo();

				$stmt = $pdo->prepare("SELECT * FROM compras WHERE id_fatura = :id_fatura");
				$stmt->execute([':id_fatura' => $explode]);

				$dados = $stmt->fetch(PDO::FETCH_ASSOC);

				$status_fatura = $this->website_getDadosFatura($dados['id_fatura'], 'status');
				$statusN;

				switch ($status_fatura) {
					case 0:
						$statusN = "<span class='badge badge-danger'>Aguardando Pagamento</span>";
						break;
					
					case 1:
						$statusN = "<span class='badge badge-success'>Pago</span>";
						break;
				}

				echo "<tr>
                  <td>1</td>
                  <td>{$dados['nome_produto']}</td>
                  <td>{$dados['data_compra']}</td>
                  <td class='text-danger'>{$this->website_getDadosFatura($dados['id_fatura'], 'data_vencimento')}</td>
                  <td>R$ {$this->website_getDadosFatura($dados['id_fatura'], 'preco')}</td>
                  <td>{$statusN}</td>
                </tr>";
			}
		}


		public function website_verficaFaturaCliente($id){
			$id_cliente = $this->website_getDetailsCompra($id, "id_comprador");

			if($id_cliente != $_SESSION['userEmail']){
				website::website_direciona("dashboard");
				exit();
			}
		}


		public static function website_alterarDadosCliente(){
			if(isset($_POST['alt']) && $_POST['alt'] == "cad"){

				try{
					$pdo = db::pdo();
					$stmt = $pdo->prepare("UPDATE clientes SET 
						nome = :nome, 
						endereco = :endereco, 
						complemento = :complemento, 
						senha = :senha, 
						cep = :cep, 
						telefone = :telefone, 
						bairro = :bairro, 
						cidade = :cidade, 
						estado = :estado 
						WHERE email = :email");

					$stmt->execute([':nome' => $_POST['nome'],
								':endereco' => $_POST['endereco'],
								':complemento' => $_POST['complemento'],
								':senha' => $_POST['senha'],
								':cep' => $_POST['cep'],
								':telefone' => $_POST['telefone'],
								':bairro' => $_POST['bairro'],
								':cidade' => $_POST['cidade'],
								':estado' => $_POST['estado'],
								':email' => $_SESSION['userEmail']]);

					$total = $stmt->rowCount();

					if($total > 0){
						echo "<br><br><div class='alert alert-succes'> Dados Alterados com sucesso!</div>";
						self::website_direciona("me");
					}

				}catch(PDOException $e){
					$e->getMessage();
				}
			}
		}

		public function website_cliente_compras(){
			$pdo = db::pdo();
			$stmt = $pdo->prepare("SELECT * FROM compras WHERE id_comprador = :id_comprador ORDER BY id DESC");
			$stmt->execute([':id_comprador' => $_SESSION['userEmail']]);

			$total = $stmt->rowCount();
			if($total > 0){
			while ($dados = $stmt->fetch(PDO::FETCH_ASSOC)) {
				$msgCompra;
				$msgFatura;

				switch($dados['status']){

					case 0:
						$msgCompra = "<span class='badge badge-warning'>Processando</span>";
					break;

					case 1:
						$msgCompra = "<span class='badge badge-success'>Entregue</span>";
					break;

				}

				switch($this->website_getDadosFatura($dados['id_fatura'], "status")){

					case 0:
						$msgFatura = "<span class='badge badge-danger'>Aguardando Pagamento</span>";
					break;

					case 1:
						$msgFatura = "<span class='badge badge-success'>Pago</span>";
					break;

				}

				echo "<tr>
			<td>{$dados['id']}</td>
			<td>{$dados['nome_produto']}</td>
			<td>{$msgCompra}</td>
			<td>{$msgFatura}</td>
			<td><a href='fatura/{$dados['id_fatura']}' class='btn btn-primary btn-sm'>Ver Detalhes</a></td>
		</tr>";
				}
			}
		}

		public function website_cliente_faturas(){
			$pdo = db::pdo();
			$stmt = $pdo->prepare("SELECT * FROM faturas WHERE id_cliente = :id_cliente ORDER BY id DESC");
			$stmt->execute([':id_cliente' => $_SESSION['userEmail']]);

			$total = $stmt->rowCount();
			if($total > 0){
			while ($dados = $stmt->fetch(PDO::FETCH_ASSOC)) {
				$msgFatura;

				switch($dados['status']){

					case 0:
						$msgFatura = "<span class='badge badge-danger'>Aguardando Pagamento</span>";
					break;
					
					case 1:
						$msgFatura = "<span class='badge badge-success'>Pago</span>";
					break;

				}

				echo "<tr>
		  <td>{$dados['id']}</td>
		  <td>{$this->website_getDetailsCompra($dados['id'], "nome_produto")}</td>
		  <td>{$msgFatura}</td>
		  <td><a href='fatura/{$dados['id']}' class='btn btn-primary btn-sm'>Pagar</a></td>
		</tr>";
				}
			}
		}

		public static function website_verifica_cadastro($email){
			$pdo = db::pdo();

			$stmt = $pdo->prepare("SELECT * FROM clientes WHERE email = :email");
			$stmt->execute([':email' => $email]);

			return $stmt->rowCount();
		}

		public static function website_register(){
			if(isset($_POST['cad']) && $_POST['cad'] == "astro"){
				$total = self::website_verifica_cadastro($_POST['email']);

				if($total > 0){
					echo "<div class='alert alert-danger'>Email já cadastrado! Por favor, tente outro!</div>";
				}else{
					$pdo = db::pdo();

					$stmt = $pdo->prepare("INSERT INTO clientes 
						(nome, 
						email,
						telefone,
						senha,
						complemento,
						cep,
						endereco,
						numero,
						bairro,
						cidade,
						estado
						) VALUES 
						(:nome,
						:email,
						:telefone,
						:senha,
						:complemento,
						:cep,
						:endereco,
						:numero,
						:bairro,
						:cidade,
						:estado)");

					$stmt->execute([':nome' => $_POST['nome'],
								':email' => $_POST['email'],
								':telefone' => $_POST['telefone'],
								':senha' => $_POST['senha'],
								':complemento' => $_POST['complemento'],
								':cep' => $_POST['cep'],
								':endereco' => $_POST['endereco'],
								':numero' => $_POST['numero'],
								':bairro' => $_POST['bairro'],
								':cidade' => $_POST['cidade'],
								':estado' => $_POST['estado']
								]);

					$result = $stmt->rowCount();

					if($result > 0){
						echo "<div class='alert alert-success'>Cadastro Efetuado com sucesso!</div>";
						self::website_direciona("login");
					}

				}
			}
		}


		public static function website_admin_getCategorias(){
			$pdo = db::pdo();

			$stmt = $pdo->prepare("SELECT * FROM categorias ORDER BY categoria ASC");
			$stmt->execute();
			$total = $stmt->rowCount();

			if($total > 0){
				while ($dados = $stmt->fetch(PDO::FETCH_ASSOC)) {
					echo "<option value='{$dados['id']}'>{$dados['categoria']}</option>";
				}
			}
		}

		public static function website_admin_getCategoriaN(){
			$pdo = db::pdo();

			$stmt = $pdo->prepare("SELECT * FROM categorias ORDER BY categoria ASC");
			$stmt->execute();
			$total = $stmt->rowCount();

			if($total > 0){
				while ($dados = $stmt->fetch(PDO::FETCH_ASSOC)) {
					echo "<optgroup label='{$dados['categoria']}'>".
					self::website_admin_getSubCategorias($dados['id'])."
					</optgroup>";
				}
			}
		}

		public static function website_admin_getSubCategorias($id){
			$pdo = db::pdo();

			$stmt = $pdo->prepare("SELECT * FROM subcategorias WHERE id_categoria = :id_categoria ORDER BY subcategoria ASC");
			$stmt->execute([':id_categoria' => $id]);
			$total = $stmt->rowCount();

			if($total > 0){
				while ($dados = $stmt->fetch(PDO::FETCH_ASSOC)) {
					return "<option value='{$dados['id']}'>{$dados['subcategoria']}</option>";
				}
			}
		}

		public static function website_admin_cadastrarProduto(){
			if(isset($_POST['env']) && $_POST['env'] == "prod"){
				if($_FILES['produtofile']['size'] <= 0){
					echo "<div class='alert alert-danger'>Insira uma imagem para prosseguir</div>";
				}else{
					$pdo = db::pdo();

					$uploaddir = '../images/uploads/';
					$uploaddirN = 'images/uploads/';
					$uploadfile = $uploaddir . basename($_FILES['produtofile']['name']);
					$uploadfileN = $uploaddirN . basename($_FILES['produtofile']['name']);

					$stmt = $pdo->prepare("INSERT INTO produtos 
						(nome,
						foto,
						tipo_fatura,
						estoque,
						preco,
						categoria,
						detalhes) 

						VALUES

						(:nome, 
						:foto, 
						:tipo_fatura, 
						:estoque, 
						:preco,
						:categoria,
						:detalhes)");
					$stmt->execute([
						':nome' => $_POST['nome'],
						':foto' => $uploadfileN,
						':tipo_fatura' => $_POST['tipo_fatura'],
						':estoque' => $_POST['estoque'],
						':preco' => $_POST['valor'],
						':categoria' => $_POST['categoria'],
						':detalhes' => $_POST['detalhes']]);

					$result = $stmt->rowCount();

					if($result > 0){
						echo "<div class='alert alert-success'>produto cadastrado com sucesso!</div>";
						move_uploaded_file($_FILES['produtofile']['tmp_name'], $uploadfile);
					}else{
						echo "<div class='alert alert-danger'>Erro ao cadastrar</div>";
					}
				}
			}
		}

		public static function website_admin_getNomeCategoria($id){
			$pdo = db::pdo();
			$stmt = $pdo->prepare("SELECT subcategoria FROM subcategorias WHERE id = :id");
			$stmt->execute([':id' => $id]);

			$dados = $stmt->fetch(PDO::FETCH_ASSOC);

			return $dados['subcategoria'];
		}

		public static function website_admin_getInfosCompras($id, $val){
			$pdo = db::pdo();
			$stmt = $pdo->prepare("SELECT * FROM compras WHERE id = :id");
			$stmt->execute([':id' => $id]);

			$dados = $stmt->fetch(PDO::FETCH_ASSOC);

			return $dados[$val];
		}

		public static function website_admin_getInfosFromIdFatura($id, $val){
			$pdo = db::pdo();
			$stmt = $pdo->prepare("SELECT * FROM compras WHERE id_fatura = :id");
			$stmt->execute([':id' => $id]);

			$dados = $stmt->fetch(PDO::FETCH_ASSOC);

			return $dados[$val];
		}

		public static function website_admin_buscarproduto(){
			if(isset($_POST['env']) && $_POST['env'] == "busca"){
				$busca = "%{$_POST['resultado']}%";

				$pdo = db::pdo();

				$stmt = $pdo->prepare("SELECT * FROM produtos WHERE nome LIKE :nome OR preco LIKE :preco");
				$stmt->execute([':nome' => $busca, ':preco' => $busca]);
				$result = $stmt->rowCount();

				if($result > 0){
					while ($dados = $stmt->fetch(PDO::FETCH_ASSOC)) {
						echo "<tr>
				  <td><img src='../{$dados['foto']}' width='30'></td>
				  <td>{$dados['nome']}</td>
				  <td>{$dados['preco']}</td>
				  <td><span class='badge badge-dark'>".self::website_admin_getNomeCategoria($dados['categoria'])."</span></td>
				  <td>
				    <a href='editar-produto/{$dados['id']}' class='btn btn-outline-primary btn-sm'>Editar</a>
				    <a href='deletar-produto/{$dados['id']}' class='btn btn-outline-danger btn-sm'>Deletar</a>
				  </td>
				</tr>";
					}
				}
			}
		}

		public static function website_admin_getProdutos(){
				$pdo = db::pdo();

				$stmt = $pdo->prepare("SELECT * FROM produtos ORDER BY id DESC");
				$stmt->execute();
				$total = $stmt->rowCount();

				if($total > 0){
					while($dados = $stmt->fetch(PDO::FETCH_ASSOC)){
						echo "<tr>
				  <td><img src='../{$dados['foto']}' width='30'></td>
				  <td>{$dados['nome']}</td>
				  <td>R$ {$dados['preco']}</td>
				  <td><span class='badge badge-dark'>".self::website_admin_getNomeCategoria($dados['categoria'])."</span></td>
				  <td>
				    <a href='editar-produto/{$dados['id']}' class='btn btn-outline-primary btn-sm'>Editar</a>
				    <a href='deletar-produto/{$dados['id']}' class='btn btn-outline-danger btn-sm'>Deletar</a>
				  </td>
				</tr>";
					}
				}
		}

		public static function website_admin_altProduto($id){
			if(isset($_POST['alt']) && $_POST['alt'] == "prod"){
				$pdo = db::pdo();

				$stmt = $pdo->prepare("UPDATE produtos SET 
					nome = :nome,
					estoque = :estoque,
					preco = :preco,
					tipo_fatura = :tipo_fatura,
					categoria = :categoria,
					detalhes = :detalhes WHERE id = :id");
				$stmt->execute(
					[':nome' => $_POST['nome'],
					':estoque' => $_POST['estoque'],
					':preco' => $_POST['valor'],
					':tipo_fatura' => $_POST['tipo_fatura'],
					':categoria' => $_POST['categoria'],
					':detalhes' => $_POST['detalhes'],
					':id' => $id]);
				$total = $stmt->rowCount();

				if($total > 0){
					echo "<div class='alert alert-success'>Dados Alterados com sucesso!</div>";
					self::website_direciona("editar-produto/{$id}");
				}else{
					echo "<div class='alert alert-danger'>Erro ao alterar</div>";
				}
			}
		}

		public static function website_admin_delete($tabela, $coluna, $id, $backpage){
			$pdo = db::pdo();

			$stmt = $pdo->prepare("DELETE FROM {$tabela} WHERE {$coluna} = :id");
			$stmt->execute([':id' => $id]);
			$count = $stmt->rowCount();

			if($count > 0){
				if($backpage != false){
					self::website_direciona($backpage);	
				}
			}else{
				echo "<div class='alert alert-danger'>Erro ao alterar</div>";
			}
		}

		public static function website_admin_addCategorias(){
			if(isset($_POST['alt']) && $_POST['alt'] == "cat"){
				$pdo = db::pdo();

				$stmt = $pdo->prepare("INSERT INTO categorias (categoria, descricao) VALUES (:categoria, :descricao)");
				$stmt->execute([':categoria' => $_POST['categoria'],
								':descricao' => $_POST['descricao']]);
				$total = $stmt->rowCount();

				if($total > 0){
					echo "<br><div class='alert alert-success'>Categoria criada com sucesso!</div>";
					self::website_direciona("gerenciar-categorias");
				}else{
					echo "<br><div class='alert alert-danger'>Erro ao adicionar</div>";
				}
			}
		}

		public static function website_admin_addSubcategorias(){
			if(isset($_POST['alt']) && $_POST['alt'] == "cat"){
				$pdo = db::pdo();

				$stmt = $pdo->prepare("INSERT INTO subcategorias (id_categoria, subcategoria, descricao) VALUES (:id_categoria, :subcategoria, :descricao)");
				$stmt->execute([':id_categoria' => $_POST['categoria'],
								':subcategoria' => $_POST['subcategoria'],
								':descricao' => $_POST['descricao']]);
				$total = $stmt->rowCount();

				if($total > 0){
					echo "<br><div class='alert alert-success'>Subcategoria criada com sucesso!</div>";
					self::website_direciona("gerenciar-categorias");
				}else{
					echo "<br><div class='alert alert-danger'>Erro ao adicionar</div>";
				}
			}
		}

		public static function website_admin_getCategoria(){
			$pdo = db::pdo();

			$stmt = $pdo->prepare("SELECT * FROM categorias ORDER BY categoria ASC");
			$stmt->execute();

			$total = $stmt->rowCount();

			if($total > 0){
				while ($dados = $stmt->fetch(PDO::FETCH_ASSOC)) {
			echo "<tr>
                  <td>{$dados['id']}</td>
                  <td>{$dados['categoria']}</td>
                  <td>
                    <a href='deletar-categoria/{$dados['id']}' class='btn btn-outline-danger btn-sm'>Deletar Categoria</a>
                  </td>
                </tr>";
				}
			}
		}

		public static function website_admin_getSubCategoria(){
			$pdo = db::pdo();

			$stmt = $pdo->prepare("SELECT * FROM subcategorias ORDER BY subcategoria ASC");
			$stmt->execute();

			$total = $stmt->rowCount();

			if($total > 0){
				while ($dados = $stmt->fetch(PDO::FETCH_ASSOC)) {
			echo "<tr>
                  <td>{$dados['id']}</td>
                  <td>{$dados['subcategoria']}</td>
                  <td>
                    <a href='deletar-subcategoria/{$dados['id']}' class='btn btn-outline-danger btn-sm'>Deletar Subcategoria</a>
                  </td>
                </tr>";
				}
			}
		}

		public static function website_admin_getDadosCliente($id, $val){
				$pdo = db::pdo();

				$stmt = $pdo->prepare("SELECT * FROM clientes WHERE email = :email");
				$stmt->execute([':email' => $id]);

				$dados = $stmt->fetch(PDO::FETCH_ASSOC);

				return $dados[$val];
		}

		public static function website_admin_modalDetailProduto($id, $informacoes){
				echo "<div class='modal fade' id='exampleModal{$id}' tabindex='-1' role='dialog' aria-labelledby='exampleModalLabel{$id}' aria-hidden='true'>
				  <div class='modal-dialog' role='document'>
				    <div class='modal-content'>
				      <div class='modal-header'>
				        <h5 class='modal-title' id='exampleModalLabel{$id}'>Informações Adicionais</h5>
				        <button type='button' class='close' data-dismiss='modal' aria-label='Close'>
				          <span aria-hidden='true'>&times;</span>
				        </button>
				      </div>
				      <div class='modal-body'>
				       {$informacoes}
				      </div>
				    </div>
				  </div>
				</div>";
		}

		public static function website_admin_modalDetailCliente($id, $cliente){
			$nome = self::website_admin_getDadosCliente($cliente, "nome");
			$telefone = self::website_admin_getDadosCliente($cliente, "telefone");
			$endereco = self::website_admin_getDadosCliente($cliente, "endereco");
			$complemento = self::website_admin_getDadosCliente($cliente, "complemento");
			$bairro = self::website_admin_getDadosCliente($cliente, "bairro");
			$estado = self::website_admin_getDadosCliente($cliente, "estado");
			$cep = self::website_admin_getDadosCliente($cliente, "cep");

				echo "<div class='modal fade' id='exampleModal{$id}' tabindex='-1' role='dialog' aria-labelledby='exampleModalLabel{$id}' aria-hidden='true'>
				  <div class='modal-dialog' role='document'>
				    <div class='modal-content'>
				      <div class='modal-header'>
				        <h5 class='modal-title' id='exampleModalLabel{$id}'>Vendo Cliente {$nome}</h5>
				        <button type='button' class='close' data-dismiss='modal' aria-label='Close'>
				          <span aria-hidden='true'>&times;</span>
				        </button>
				      </div>
				      <div class='modal-body'>
				       Nome: {$nome}<br>
				       Email: {$cliente}<br>
				       Telefone: {$telefone}<br>
				       Endereço: {$endereco}<br>
				       Cep: {$cep}<br>
				       Bairro: {$bairro}<br>
				       Estado: {$estado}<br>

				      </div>
				    </div>
				  </div>
				</div>";
		}

		public static function website_admin_geCompras(){
			$pdo = db::pdo();

			$stmt = $pdo->prepare("SELECT * FROM compras ORDER BY id DESC");
			$stmt->execute();
			$total = $stmt->rowCount();

			if($total > 0){
				while ($dados = $stmt->fetch(PDO::FETCH_ASSOC)) {
					$status = self::website_getDadosFatura($dados['id_fatura'], "status");

					if($status == 1 && $dados['status'] == 0){
						echo "<tr>
				  <td>1</td>
				  <td><a href='ver-cliente/{$dados['id_comprador']}' target='_blank'>".self::website_admin_getDadosCliente($dados['id_comprador'], "nome")."</a></td>
				  <td>R$ ".self::website_getDadosFatura($dados['id_fatura'], "preco")."</td>
				  <td>{$dados['external_reference']}</td>
				  <td>
				    <a class='btn btn-outline-primary btn-sm' data-toggle='modal' data-target='#exampleModal{$dados['id']}'>Ver detalhes</a>
				    <a href='marcar-entregue/{$dados['id_fatura']}' class='btn btn-outline-success btn-sm'>Entregue</a>
				    <a href='deletar-venda/{$dados['id_fatura']}' class='btn btn-outline-danger btn-sm'>Deletar</a>
				  </td>
				</tr>";
				self::website_admin_modalDetailProduto($dados['id'], $dados['detalhes']);
					}
				}
			}
		}


		public static function website_admin_geComprasCliente($cliente){
			$pdo = db::pdo();

			$stmt = $pdo->prepare("SELECT * FROM compras WHERE id_comprador = :id_comprador ORDER BY id DESC");
			$stmt->execute([':id_comprador' => $cliente]);
			$total = $stmt->rowCount();

			if($total > 0){
				while ($dados = $stmt->fetch(PDO::FETCH_ASSOC)) {
					$status = self::website_getDadosFatura($dados['id_fatura'], "status");
						echo "<tr>
				  <td>1</td>
				  <td>{$dados['nome_produto']}</td>
				  <td>R$ ".self::website_getDadosFatura($dados['id_fatura'], "preco")."</td>
				  <td>
				    <a class='btn btn-outline-primary btn-sm' data-toggle='modal' data-target='#exampleModal{$dados['id']}'>Ver detalhes</a> ";

				    if($status == 1){

				    echo "<a href='marcar-entregue/{$dados['id_fatura']}' class='btn btn-outline-success btn-sm'>Entregue</a> ";
					}
				    echo "<a href='deletar-venda/{$dados['id_fatura']}' class='btn btn-outline-danger btn-sm'>Deletar</a>
				  </td>
				</tr>";
				self::website_admin_modalDetailProduto($dados['id'], $dados['detalhes']);
				}
			}
		}

		public static function website_admin_getFaturasCliente($cliente){
			$pdo = db::pdo();


			$stmt = $pdo->prepare("SELECT * FROM faturas WHERE id_cliente = :id_cliente ORDER BY id DESC");
			$stmt->execute([':id_cliente' => $cliente]);
			$total = $stmt->rowCount();

			if($total > 0){
				while ($dados = $stmt->fetch(PDO::FETCH_ASSOC)) {
				$statusF;

				switch($dados['status']){
					case 0:
						$statusF = "<span class='badge badge-danger'>Aguardando Pagamento</span>";
					break;

					case 1:
						$statusF = "<span class='badge badge-success'>Pago</span>";
					break;
				}
			echo "<tr>
		  <td>{$dados['id']}</td>
		  <td>".self::website_admin_getInfosCompras($dados['id'],"nome_produto")."</td>
		  <td>R$ {$dados['preco']}</td>
		  <td>{$statusF}</a>
		  </td>
		</tr>";
				}
			}
		}

		public static function website_admin_geComprasConcluidas(){
			$pdo = db::pdo();

			$stmt = $pdo->prepare("SELECT * FROM compras WHERE status = :status ORDER BY id DESC");
			$stmt->execute([':status' => 1]);
			$total = $stmt->rowCount();

			if($total > 0){
				while ($dados = $stmt->fetch(PDO::FETCH_ASSOC)) {
					$status = self::website_getDadosFatura($dados['id_fatura'], "status");

						echo "<tr>
				  <td>1</td>
				  <td><a href='ver-cliente/{$dados['id_comprador']}' target='_blank'>".self::website_admin_getDadosCliente($dados['id_comprador'], "nome")."</a></td>
				  <td>R$ ".self::website_getDadosFatura($dados['id_fatura'], "preco")."</td>
				  <td>
				    <a class='btn btn-outline-primary btn-sm' data-toggle='modal' data-target='#exampleModal{$dados['id']}'>Ver detalhes</a>
				  </td>
				</tr>";
				self::website_admin_modalDetailProduto($dados['id'], $dados['detalhes']);
				}
			}
		}



		public static function website_admin_delCompras($id){
			self::website_admin_delete("compras", "id_fatura", $id, "gerenciar-compras");
			self::website_admin_delete("faturas", "id", $id, false);
			exit();
		}

		public static function website_admin_delCategoria($id){
			self::website_admin_delete("categorias", "id", $id, "gerenciar-categorias");
			self::website_admin_delete("subcategorias", "id_categoria", $id, false);
			exit();
		}


		public static function website_admin_buscarCompras(){

			if(isset($_POST['env']) && $_POST['env'] == "busca"){
				$pdo = db::pdo();
				$resultado = "%{$_POST['resultado']}%";
				$stmt = $pdo->prepare("SELECT * FROM compras WHERE id_comprador LIKE :id_comprador ORDER BY id DESC");
				$stmt->execute([':id_comprador' => $resultado]);
				$total = $stmt->rowCount();

				if($total > 0){
					while ($dados = $stmt->fetch(PDO::FETCH_ASSOC)) {
						$status = self::website_getDadosFatura($dados['id_fatura'], "status");
					
					echo "<tr>
				  <td>{$dados['id']}</td>
				  <td>{$dados['nome_produto']}</td>
				  <td><a href='ver-cliente/{$dados['id_comprador']}' target='_blank'>".self::website_admin_getDadosCliente($dados['id_comprador'], "nome")."</a></td>
				  <td>R$ ".self::website_getDadosFatura($dados['id_fatura'], "preco")."</td>
				  <td>
				    <a class='btn btn-outline-primary btn-sm' data-toggle='modal' data-target='#exampleModal{$dados['id']}'>Ver Detalhes</a>";
				    if($status == 1 && $dados['status'] == 0){
				    echo "<a href='marcar-entregue/{$dados['id']}' class='btn btn-outline-success btn-sm'>Entregue</a>";};
				   echo "<a href='deletar-venda/{$dados['id']}' class='btn btn-outline-danger btn-sm'>Deletar</a>
				  </td>
				</tr>";
				self::website_admin_modalDetailProduto($dados['id'], $dados['detalhes']);
					}
				}
			}
		}

		public static function website_admin_updateFatura($id, $backpage){
			$pdo = db::pdo();

			try{
				$stmt = $pdo->prepare("UPDATE faturas SET status = :status WHERE id = :id");
				$stmt->execute([':status' => 1, 
								':id' => $id]);

				$resultado = $stmt->rowCount();

				if($resultado > 0){
					self::website_direciona($backpage);
					exit();
				}
			}catch(PDOException $e){
				echo $e->getMessage();
			}
		}

		public static function website_admin_updateCompra($id, $backpage){
			$pdo = db::pdo();

			try{
				$stmt = $pdo->prepare("UPDATE compras SET status = :status WHERE id = :id");
				$stmt->execute([':status' => 1, 
								':id' => $id]);

				$resultado = $stmt->rowCount();

				if($resultado > 0){
					self::website_direciona($backpage);
					exit();
				}
			}catch(PDOException $e){
				echo $e->getMessage();
			}
		}


		public static function website_admin_getPendingFaturas(){
			$pdo = db::pdo();


			$stmt = $pdo->prepare("SELECT * FROM faturas WHERE status = 0 ORDER BY id DESC");
			$stmt->execute();

			$total = $stmt->rowCount();

			if($total > 0){
				while ($dados = $stmt->fetch(PDO::FETCH_ASSOC)) {
					$idVenda = self::website_admin_getInfosFromIdFatura($dados['id'], "id_fatura");
					echo "<tr>
		  <td>{$dados['id']}</td>
		  <td><a href='ver-cliente/{$dados['id_cliente']}' target='_blank'>".self::website_admin_getDadosCliente($dados['id_cliente'], "nome")."</a></td>
		  <td>".self::website_admin_getInfosFromIdFatura($dados['id'],"nome_produto")."</td>
		  <td>R$ {$dados['preco']}</td>
		  <td>".self::website_admin_getInfosFromIdFatura($dados['id'],"external_reference")."</td>
		  <td>
		    <a href='marcar-pago/{$dados['id']}' class='btn btn-outline-success btn-sm'>Marcar como pago</a> 
		    <a href='deletar-venda/{$idVenda}' class='btn btn-outline-danger btn-sm'>Deletar Fatura</a>
		  </td>
		</tr>";
				}
			}
		}

		public static function website_admin_getFaturasPagas(){
			$pdo = db::pdo();


			$stmt = $pdo->prepare("SELECT * FROM faturas WHERE status = 1 ORDER BY id DESC");
			$stmt->execute();

			$total = $stmt->rowCount();

			if($total > 0){
				while ($dados = $stmt->fetch(PDO::FETCH_ASSOC)) {
					echo "<tr>
		  <td>{$dados['id']}</td>
		  <td><a href='ver-cliente/{$dados['id_cliente']}' target='_blank'>".self::website_admin_getDadosCliente($dados['id_cliente'], "nome")."</a></td>
		  <td>".self::website_admin_getInfosFromIdFatura($dados['id'],"nome_produto")."</td>
		  <td>R$ {$dados['preco']}</td>
		</tr>";
				}
			}
		}

		public static function website_admin_buscaClientes(){
			$pdo = db::pdo();

			if(isset($_POST['env']) && $_POST['env'] == "busca"){
				$resultado = "%{$_POST['resultado']}%";
				$stmt = $pdo->prepare("SELECT * FROM clientes WHERE email LIKE :email OR nome like :email");
				$stmt->execute([':email' => $resultado]);
				$total = $stmt->rowCount();

				if($total > 0){
					while ($dados = $stmt->fetch(PDO::FETCH_ASSOC)) {
						echo "<tr>
				  <td>-></td>
				  <td><a href='ver-cliente/{$dados['email']}' target='_blank'>{$dados['nome']}</a></td>
				  <td>{$dados['email']}</td>
				  <td><a data-toggle='modal' data-target='#exampleModal{$dados['id']}' class='btn btn-outline-info btn-sm'>Ver Cliente</a></td>
				</tr>";
				self::website_admin_modalDetailCliente($dados['id'], $dados['email']);
					}
				}
			}
		}

		public static function admin_badgeCompras(){
			$pdo = db::pdo();

			$stmt = $pdo->prepare("SELECT * FROM compras WHERE status = 0");
			$stmt->execute();
			$total = $stmt->rowCount();

			if($total > 0){
				echo "<span class='badge badge-danger'>{$total}</span>";	
			}
		}

		public static function admin_badgeFaturas(){
			$pdo = db::pdo();

			$stmt = $pdo->prepare("SELECT * FROM faturas WHERE status = 0");
			$stmt->execute();
			$total = $stmt->rowCount();

			if($total > 0){
				echo "<span class='badge badge-danger'>{$total}</span>";	
			}
		}
	}


	class clientes{
		private $id;
		public $nome;
		public $email;
		public $telefone;
		public $senha;
		public $endereco;
		public $numero;
		public $complemento;
		public $cep;
		public $cidade;
		public $estado;
		public $bairro;
		public $isadmin;

		public function __construct(){
			if(isset($_SESSION['userEmail'])){
				$this->clientes_updatesInfos();
			}
		}

		public function clientes_updatesInfos(){
			$email = $_SESSION['userEmail'];

			try{
				$pdo = db::pdo();

				$stmt = $pdo->prepare("SELECT * FROM clientes WHERE email = :email");
				$stmt->execute([':email' => $email]);

				$dados = $stmt->fetch(PDO::FETCH_ASSOC);

				$this->id = $dados['id'];
				$this->nome = $dados['nome'];
				$this->email = $dados['email'];
				$this->telefone = $dados['telefone'];
				$this->senha = $dados['senha'];
				$this->endereco = $dados['endereco'];
				$this->numero = $dados['numero'];
				$this->complemento = $dados['complemento'];
				$this->cep = $dados['cep'];
				$this->bairro = $dados['bairro'];
				$this->cidade = $dados['cidade'];
				$this->estado = $dados['estado'];
				$this->isadmin = $dados['isadmin'];
			
			}catch(PDOException $e){
				return $e->getMessage();
			}
		}
	}

	class produtos{
		private $id;
		public $nome;
		public $foto;
		public $tipo_fatura;
		public $estoque;
		public $preco;
		public $categoria;
		public $detalhes;



	public function __construct($id){
		$this->produtos_setInfos($id);
	}

	public function produtos_setInfos($id){
		if(isset($id)){
			$pdo = db::pdo();
			$stmt = $pdo->prepare("SELECT * FROM produtos WHERE id = :id");
			$stmt->execute([':id' => $id]);
			$total = $stmt->rowCount();


			if($total > 0){
				$dados = $stmt->fetch(PDO::FETCH_ASSOC);
				$this->id = $dados['id'];
				$this->nome = $dados['nome'];
				$this->foto = $dados['foto'];
				$this->tipo_fatura = $dados['tipo_fatura'];
				$this->estoque = $dados['estoque'];
				$this->preco = $dados['preco'];
				$this->categoria = $dados['categoria'];
				$this->detalhes = $dados['detalhes'];
			}
		}
	}

	public function produtos_get_total(){
		switch ($this->estoque) {
			case -1:
				echo "<a href='comprar/{$this->id}' class='btn btn-primary'>Comprar</a>";
			break;

			case 0:
				echo "<code>Infelizmente estamos com o estoque zerado :(</code>";
			break;
			
			default:
				echo "<a href='comprar/{$this->id}' class='btn btn-primary'>Comprar</a>";
			break;
		}
	}

	public function produtos_vefica_login(){
		if(!isset($_SESSION['userEmail'])){
			echo "<div class='alert alert-danger'>Redirecionando para o login</div>";
			website::website_direciona("login");
			exit();
		}
	}

	public function produtos_cria_fatura($preco){
		$pdo = db::pdo();
		$data = website::get_data();
		$data_vencimento = date('d/m/Y', strtotime('+5 days'));

		$stmt = $pdo->prepare("INSERT INTO faturas (preco, data, data_vencimento, id_cliente) VALUES (:preco, :data, :data_vencimento, :id_cliente)");
		$stmt->execute([':preco' => $preco, ':data' => $data, ':data_vencimento' => $data_vencimento, ':id_cliente' => $_SESSION['userEmail']]);
		
		return $pdo->lastInsertId();
	}

	public function produtos_cria_compra($id_fatura){
		$pdo = db::pdo();
		$data = website::get_data();
		$external_reference = "ID: ".rand(1, 99999);

		$stmt = $pdo->prepare("INSERT INTO compras (id_comprador, id_fatura, nome_produto, data_compra, detalhes, external_reference) VALUES (:id_comprador, :id_fatura, :nome_produto, :data_comprada, :detalhes, :external_reference)");
		$stmt->execute([':id_comprador' => $_SESSION['userEmail'], ':id_fatura' => $id_fatura, ':nome_produto' => $_POST['nome_produto'], ':data_comprada' => $data, ':detalhes' => $_POST['detalhes'], ':external_reference' => $external_reference]);
	}

	public function produtos_reduzirEstoque($id, $estoque){
		try{
			$pdo = db::pdo();
			$nEstoque = ($estoque) - 1;

			$stmt = $pdo->prepare("UPDATE produtos SET estoque = :estoque WHERE id = :id");
			$stmt->execute([':estoque' => $nEstoque, ':id' => $id]);
		}catch(PDOException $e){
			echo $e->getMessage();
		}
	}

	public function produtos_getMax(){
		switch ($this->estoque) {
			case -1:
				echo 1;
			break;
			
			default:
				echo $this->estoque;
			break;
		}
	}

	public function produtos_verificaEstoque(){
		if($this->estoque == 0){
			echo "<br><div class='alert alert-danger'>Produto sem estoque</div>";
			exit();
		}
	}

	public function produtos_finalizar_compra($id){
		if(isset($_POST['env']) && $_POST['env'] == "compra"){
			$id_fatura = $this->produtos_cria_fatura($_POST['subtotal']);
			$this->produtos_cria_compra($id_fatura);
			$this->produtos_reduzirEstoque($id, $this->estoque);
			website::website_direciona("fatura/{$id_fatura}");
		}
	}

	public function produtos_switchEstoque(){
		switch ($this->estoque) {
			case -1:
				return "Ilimitado";	
			break;
			
			case 0:
				return "<code>0</code>";	
			break;

			default:
				return "{$this->estoque}";	
			break;
		}
	}

}

?>
